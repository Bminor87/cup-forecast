<?php

use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Enums\PredictionStatus;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Enums\TeamRole;
use App\Jobs\ScorePredictionResultJob;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

beforeEach(function () {
    $this->withoutMiddleware([
        ValidateSessionWithWorkOS::class,
    ]);
});

test('admin can create prediction question from template endpoint', function () {
    [$admin, $team] = phase9AdminContext();

    $this->actingAs($admin)
        ->withSession(['_token' => 'test-token'])
        ->from(route('admin.prediction-questions', ['team' => $team->slug]))
        ->post(route('admin.prediction-fields.templates.store', ['team' => $team->slug]), [
            '_token' => 'test-token',
            'template_key' => 'match_winner',
        ])
        ->assertRedirect(route('admin.prediction-questions', ['team' => $team->slug]));

    $this->assertDatabaseHas('prediction_fields', [
        'tournament_id' => $team->id,
        'label' => 'Match Winner',
        'scope' => PredictionScope::Match->value,
        'field_type' => PredictionFieldType::TeamPicker->value,
        'scoring_strategy_key' => 'exact_match',
    ]);
});

test('participant can access navigation pages', function () {
    [$participant, $team, $tournament] = phase9ParticipantContext();

    $this->actingAs($participant)
        ->get(route('predictions.tournament', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('predictions/tournament')
            ->where('currentTeamSlug', $team->slug),
        );

    $this->actingAs($participant)
        ->get(route('predictions.matches', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('predictions/matches')
            ->where('currentTeamSlug', $team->slug),
        );

    $this->actingAs($participant)
        ->get(route('predictions.rules', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('predictions/rules')
            ->where('currentTeamSlug', $team->slug),
        );

    expect($tournament->id)->toBe($team->id);
});

test('leaderboard page uses persisted scores for ranking', function () {
    [$admin, $team, $tournament] = phase9AdminContext();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $field = PredictionField::factory()->forTournament($tournament)->create();

    $adminPrediction = Prediction::factory()->forField($field)->create(['user_id' => $admin->id]);
    $memberPrediction = Prediction::factory()->forField($field)->create(['user_id' => $member->id]);

    $adminResult = PredictionResult::factory()->create([
        'tournament_id' => $tournament->id,
        'prediction_field_id' => $field->id,
        'context_key' => Prediction::tournamentContextKey(),
        'status' => PredictionResultStatus::Resolved,
        'value' => ['value' => $adminPrediction->value['value']],
    ]);

    $memberPrediction->update([
        'value' => ['value' => 'not-'.$adminPrediction->value['value']],
    ]);

    dispatch_sync(new ScorePredictionResultJob($adminResult->id));

    $response = $this->actingAs($admin)
        ->get(route('predictions.leaderboard', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('predictions/leaderboard')
            ->has('leaderboard', 2)
            ->where('leaderboard.0.rank', 1)
            ->where('leaderboard.1.rank', 2),
        );

    $leaderboard = collect($response->inertiaProps('leaderboard'));

    expect($leaderboard->pluck('participant')->all())
        ->toContain($admin->name, $member->name);
    expect($leaderboard->pluck('points')->every(fn (mixed $points): bool => is_int($points)))
        ->toBeTrue();
});

test('overview dashboard includes participant summary statistics', function () {
    [$participant, $team, $tournament] = phase9ParticipantContext();

    $home = TournamentTeam::factory()->forTournament($tournament)->create(['name' => 'Alpha']);
    $away = TournamentTeam::factory()->forTournament($tournament)->create(['name' => 'Beta']);

    $match = TournamentMatch::factory()->forTeams($tournament, $home, $away)->create([
        'starts_at' => now()->addHours(3),
        'locks_at' => now()->addHours(2),
    ]);

    $field = PredictionField::factory()->forTournament($tournament)->tournamentScoped()->create([
        'field_type' => PredictionFieldType::Text,
    ]);

    $prediction = Prediction::query()->create([
        'tournament_id' => $tournament->id,
        'prediction_field_id' => $field->id,
        'user_id' => $participant->id,
        'context_key' => Prediction::tournamentContextKey(),
        'value' => ['value' => 'Alpha'],
        'status' => PredictionStatus::Submitted,
        'submitted_at' => now(),
        'locked_at' => null,
    ]);

    $result = PredictionResult::factory()->create([
        'tournament_id' => $tournament->id,
        'prediction_field_id' => $field->id,
        'context_key' => Prediction::tournamentContextKey(),
        'status' => PredictionResultStatus::Resolved,
        'value' => ['value' => $prediction->value['value']],
    ]);

    dispatch_sync(new ScorePredictionResultJob($result->id));

    $response = $this->actingAs($participant)
        ->get(route('dashboard', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard'),
        );

    $summary = $response->inertiaProps('participantSummary');

    expect($summary)->toBeArray();
    expect($summary)->toHaveKeys(['rank', 'points', 'submitted_predictions', 'remaining_predictions']);
    expect(is_int($summary['points']))->toBeTrue();
    expect(is_int($summary['submitted_predictions']))->toBeTrue();
    expect($summary['submitted_predictions'])->toBeGreaterThanOrEqual(0);

    expect($response->inertiaProps('upcomingMatches'))->toBeArray();
    expect($response->inertiaProps('upcomingDeadlines'))->toBeArray();
});

/**
 * @return array{0: User, 1: Team, 2: Tournament}
 */
function phase9AdminContext(): array
{
    $admin = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $admin->forceFill(['current_team_id' => $team->id])->save();

    $tournament = Tournament::query()->findOrFail($team->id);

    return [$admin, $team, $tournament];
}

/**
 * @return array{0: User, 1: Team, 2: Tournament}
 */
function phase9ParticipantContext(): array
{
    $participant = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($participant, ['role' => TeamRole::Member->value]);
    $participant->forceFill(['current_team_id' => $team->id])->save();

    $tournament = Tournament::query()->findOrFail($team->id);

    return [$participant, $team, $tournament];
}
