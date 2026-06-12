<?php

use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionOptionSource;
use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Enums\PredictionStatus;
use App\Domain\Tournaments\Enums\PredictionVisibility;
use App\Domain\Tournaments\Models\Player;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutMiddleware();
});

test('participant can submit tournament prediction', function () {
    [$participant, $team, $tournament] = participantContext();

    $field = PredictionField::factory()->forTournament($tournament)->tournamentScoped()->create([
        'field_type' => PredictionFieldType::Text,
        'validation_schema' => ['required' => true],
    ]);

    $this->actingAs($participant)
        ->putJson(route('predictions.upsert', [
            'current_team' => $team->slug,
            'predictionField' => $field->id,
        ]), [
            'value' => 'Finland',
            'status' => PredictionStatus::Submitted->value,
        ])
        ->assertOk()
        ->assertJsonPath('status', PredictionStatus::Submitted->value)
        ->assertJsonPath('locked', false);

    $this->assertDatabaseHas('predictions', [
        'tournament_id' => $tournament->id,
        'prediction_field_id' => $field->id,
        'user_id' => $participant->id,
        'context_key' => Prediction::tournamentContextKey(),
    ]);
});

test('participant can submit match prediction', function () {
    [$participant, $team, $tournament] = participantContext();
    [$match] = tournamentMatchContext($tournament);

    $field = PredictionField::factory()->forTournament($tournament)->matchScoped()->create([
        'field_type' => PredictionFieldType::Text,
        'validation_schema' => ['required' => true],
    ]);

    $this->actingAs($participant)
        ->putJson(route('predictions.upsert', [
            'current_team' => $team->slug,
            'predictionField' => $field->id,
        ]), [
            'tournament_match_id' => $match->id,
            'value' => '2-1',
            'status' => PredictionStatus::Submitted->value,
        ])
        ->assertOk();

    $this->assertDatabaseHas('predictions', [
        'prediction_field_id' => $field->id,
        'user_id' => $participant->id,
        'tournament_match_id' => $match->id,
        'context_key' => Prediction::contextKeyForMatch($match->id),
    ]);
});

test('participant can update existing prediction', function () {
    [$participant, $team, $tournament] = participantContext();

    $field = PredictionField::factory()->forTournament($tournament)->tournamentScoped()->create([
        'field_type' => PredictionFieldType::Text,
        'validation_schema' => ['required' => true],
    ]);

    $prediction = Prediction::factory()->forField($field)->create([
        'user_id' => $participant->id,
        'value' => ['value' => 'Sweden'],
    ]);

    $this->actingAs($participant)
        ->putJson(route('predictions.upsert', [
            'current_team' => $team->slug,
            'predictionField' => $field->id,
        ]), [
            'value' => 'Finland',
            'status' => PredictionStatus::Submitted->value,
        ])
        ->assertOk();

    $prediction->refresh();

    expect($prediction->value['value'])->toBe('Finland');
});

test('autosave semantics keep one row and latest value', function () {
    [$participant, $team, $tournament] = participantContext();

    $field = PredictionField::factory()->forTournament($tournament)->tournamentScoped()->create([
        'field_type' => PredictionFieldType::Text,
        'validation_schema' => ['required' => true],
    ]);

    $this->actingAs($participant)
        ->putJson(route('predictions.upsert', [
            'current_team' => $team->slug,
            'predictionField' => $field->id,
        ]), [
            'value' => 'A',
            'status' => PredictionStatus::Submitted->value,
        ])
        ->assertOk();

    $this->actingAs($participant)
        ->putJson(route('predictions.upsert', [
            'current_team' => $team->slug,
            'predictionField' => $field->id,
        ]), [
            'value' => 'B',
            'status' => PredictionStatus::Submitted->value,
        ])
        ->assertOk()
        ->assertJsonPath('status', PredictionStatus::Submitted->value);

    $count = Prediction::query()
        ->where('prediction_field_id', $field->id)
        ->where('user_id', $participant->id)
        ->where('context_key', Prediction::tournamentContextKey())
        ->count();

    $latest = Prediction::query()
        ->where('prediction_field_id', $field->id)
        ->where('user_id', $participant->id)
        ->where('context_key', Prediction::tournamentContextKey())
        ->firstOrFail();

    expect($count)->toBe(1);
    expect($latest->value['value'])->toBe('B');
});

test('tournament lock prevents edits after tournament start', function () {
    [$participant, $team, $tournament] = participantContext();

    $tournament->update(['starts_at' => now()->subMinute()]);

    $field = PredictionField::factory()->forTournament($tournament)->tournamentScoped()->create([
        'field_type' => PredictionFieldType::Text,
        'validation_schema' => ['required' => true],
    ]);

    Prediction::factory()->forField($field)->create([
        'user_id' => $participant->id,
        'value' => ['value' => 'Before lock'],
        'status' => PredictionStatus::Submitted,
    ]);

    $this->actingAs($participant)
        ->putJson(route('predictions.upsert', [
            'current_team' => $team->slug,
            'predictionField' => $field->id,
        ]), [
            'value' => 'After lock',
            'status' => PredictionStatus::Submitted->value,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('prediction');
});

test('match lock prevents edits after lock time', function () {
    [$participant, $team, $tournament] = participantContext();
    [$match] = tournamentMatchContext($tournament, now()->subMinute());

    $field = PredictionField::factory()->forTournament($tournament)->matchScoped()->create([
        'field_type' => PredictionFieldType::Text,
        'validation_schema' => ['required' => true],
    ]);

    Prediction::factory()->forField($field)->forMatch($match)->create([
        'user_id' => $participant->id,
        'value' => ['value' => '1-0'],
        'status' => PredictionStatus::Submitted,
    ]);

    $this->actingAs($participant)
        ->putJson(route('predictions.upsert', [
            'current_team' => $team->slug,
            'predictionField' => $field->id,
        ]), [
            'tournament_match_id' => $match->id,
            'value' => '2-0',
            'status' => PredictionStatus::Submitted->value,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('prediction');
});

test('visibility controls result payload on participant page', function () {
    [$participant, $team, $tournament] = participantContext();

    $field = PredictionField::factory()->forTournament($tournament)->tournamentScoped()->create([
        'field_type' => PredictionFieldType::Text,
        'visibility' => PredictionVisibility::HiddenUntilResult,
        'validation_schema' => ['required' => true],
    ]);

    PredictionResult::factory()->create([
        'tournament_id' => $tournament->id,
        'prediction_field_id' => $field->id,
        'status' => PredictionResultStatus::Pending,
        'value' => ['value' => 'Hidden'],
    ]);

    $this->actingAs($participant)
        ->get(route('predictions.index', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('predictions/index')
            ->where('tournamentPredictions.0.result_is_visible', false)
            ->where('tournamentPredictions.0.result_value', null),
        );

    PredictionResult::query()
        ->where('prediction_field_id', $field->id)
        ->update([
            'status' => PredictionResultStatus::Resolved,
            'resolved_at' => now(),
        ]);

    $this->actingAs($participant)
        ->get(route('predictions.index', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('predictions/index')
            ->where('tournamentPredictions.0.result_is_visible', true)
            ->where('tournamentPredictions.0.result_value', 'Hidden'),
        );
});

test('invalid prediction payload returns validation errors', function () {
    [$participant, $team, $tournament] = participantContext();

    $field = PredictionField::factory()->forTournament($tournament)->tournamentScoped()->create([
        'field_type' => PredictionFieldType::Number,
        'validation_schema' => ['required' => true],
    ]);

    $this->actingAs($participant)
        ->putJson(route('predictions.upsert', [
            'current_team' => $team->slug,
            'predictionField' => $field->id,
        ]), [
            'value' => 'not-a-number',
            'status' => PredictionStatus::Submitted->value,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('value');
});

test('match team picker only exposes teams in that match', function () {
    [$participant, $team, $tournament] = participantContext();
    [$match, $home, $away] = tournamentMatchContext($tournament);
    TournamentTeam::factory()->forTournament($tournament)->create(['name' => 'Outside Team']);

    PredictionField::factory()
        ->forTournament($tournament)
        ->matchScoped()
        ->teamPicker(PredictionOptionSource::MatchTeams)
        ->create([
            'label' => 'Match Winner',
            'key' => 'match_winner_test',
            'validation_schema' => ['required' => true],
        ]);

    $this->actingAs($participant)
        ->get(route('predictions.index', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('predictions/index')
            ->where('matchPredictions.0.fields.0.options', [
                ['value' => $home->id, 'label' => $home->name],
                ['value' => $away->id, 'label' => $away->name],
            ]),
        );
});

test('match player picker only exposes players from both match teams', function () {
    [$participant, $team, $tournament] = participantContext();
    [$match, $home, $away] = tournamentMatchContext($tournament);

    $homePlayer = Player::factory()->forTournamentTeam($tournament, $home)->create(['name' => 'Home Hero']);
    $awayPlayer = Player::factory()->forTournamentTeam($tournament, $away)->create(['name' => 'Away Hero']);
    $outsideTeam = TournamentTeam::factory()->forTournament($tournament)->create(['name' => 'Outside Team']);
    Player::factory()->forTournamentTeam($tournament, $outsideTeam)->create(['name' => 'Outside Hero']);

    PredictionField::factory()
        ->forTournament($tournament)
        ->matchScoped()
        ->playerPicker(PredictionOptionSource::MatchPlayers)
        ->create([
            'label' => 'MVP',
            'key' => 'match_mvp_test',
            'validation_schema' => ['required' => true],
        ]);

    $this->actingAs($participant)
        ->get(route('predictions.index', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('predictions/index')
            ->where('matchPredictions.0.fields.0.options', [
                ['value' => $awayPlayer->id, 'label' => $awayPlayer->name.' ('.$away->name.')'],
                ['value' => $homePlayer->id, 'label' => $homePlayer->name.' ('.$home->name.')'],
            ]),
        );
});

test('match team picker rejects team outside current match context', function () {
    [$participant, $team, $tournament] = participantContext();
    [$match] = tournamentMatchContext($tournament);
    $outsideTeam = TournamentTeam::factory()->forTournament($tournament)->create(['name' => 'Outside Team']);

    $field = PredictionField::factory()
        ->forTournament($tournament)
        ->matchScoped()
        ->teamPicker(PredictionOptionSource::MatchTeams)
        ->create([
            'validation_schema' => ['required' => true],
        ]);

    $this->actingAs($participant)
        ->putJson(route('predictions.upsert', [
            'current_team' => $team->slug,
            'predictionField' => $field->id,
        ]), [
            'tournament_match_id' => $match->id,
            'value' => $outsideTeam->id,
            'status' => PredictionStatus::Submitted->value,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('value');
});

test('match player picker rejects player outside current match teams', function () {
    [$participant, $team, $tournament] = participantContext();
    [$match] = tournamentMatchContext($tournament);
    $outsideTeam = TournamentTeam::factory()->forTournament($tournament)->create(['name' => 'Outside Team']);
    $outsidePlayer = Player::factory()->forTournamentTeam($tournament, $outsideTeam)->create();

    $field = PredictionField::factory()
        ->forTournament($tournament)
        ->matchScoped()
        ->playerPicker(PredictionOptionSource::MatchPlayers)
        ->create([
            'validation_schema' => ['required' => true],
        ]);

    $this->actingAs($participant)
        ->putJson(route('predictions.upsert', [
            'current_team' => $team->slug,
            'predictionField' => $field->id,
        ]), [
            'tournament_match_id' => $match->id,
            'value' => $outsidePlayer->id,
            'status' => PredictionStatus::Submitted->value,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('value');
});

/**
 * @return array{0: User, 1: Team, 2: Tournament}
 */
function participantContext(): array
{
    $participant = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($participant, ['role' => TeamRole::Member->value]);
    $participant->forceFill(['current_team_id' => $team->id])->save();

    $tournament = Tournament::query()->findOrFail($team->id);
    $tournament->update(['starts_at' => now()->addDay()]);

    return [$participant, $team, $tournament];
}

/**
 * @return array{0: TournamentMatch, 1: TournamentTeam, 2: TournamentTeam}
 */
function tournamentMatchContext(Tournament $tournament, ?DateTimeInterface $lockTime = null): array
{
    $home = TournamentTeam::factory()->forTournament($tournament)->create();
    $away = TournamentTeam::factory()->forTournament($tournament)->create();

    $match = TournamentMatch::factory()->forTeams($tournament, $home, $away)->create([
        'starts_at' => now()->addHour(),
        'locks_at' => $lockTime ?? now()->addMinutes(30),
    ]);

    return [$match, $home, $away];
}
