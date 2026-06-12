<?php

use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Enums\PredictionStatus;
use App\Domain\Tournaments\Enums\PredictionVisibility;
use App\Domain\Tournaments\Locking\PredictionLockService;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Domain\Tournaments\Scoring\ExactMatchStrategy;
use App\Domain\Tournaments\Scoring\PredictionScoringService;
use App\Domain\Tournaments\Validation\PredictionSubmissionValidationService;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Inertia\Testing\AssertableInertia;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

beforeEach(function () {
    $this->withoutMiddleware([
        ValidateCsrfToken::class,
        VerifyCsrfToken::class,
        ValidateSessionWithWorkOS::class,
    ]);
});

test('prediction field creation stores phase 5 configuration', function () {
    $team = Team::factory()->create();
    $tournament = Tournament::query()->findOrFail($team->id);

    $field = PredictionField::factory()->forTournament($tournament)->create([
        'scope' => PredictionScope::Tournament,
        'field_type' => PredictionFieldType::Text,
        'label' => 'Winner country',
        'key' => 'winner_country',
        'visibility' => PredictionVisibility::HiddenUntilResult,
        'scoring_strategy_key' => 'exact_match',
    ]);

    expect($field->tournament->is($tournament))->toBeTrue();

    $this->assertDatabaseHas('prediction_fields', [
        'id' => $field->id,
        'tournament_id' => $tournament->id,
        'key' => 'winner_country',
    ]);
});

test('prediction submission can be validated and stored', function () {
    $field = PredictionField::factory()->create([
        'field_type' => PredictionFieldType::Text,
        'validation_schema' => ['required' => true, 'min' => 2],
    ]);

    $user = User::factory()->create();

    $validated = app(PredictionSubmissionValidationService::class)
        ->validate($field, ['value' => 'Finland']);

    $prediction = Prediction::query()->create([
        'tournament_id' => $field->tournament_id,
        'prediction_field_id' => $field->id,
        'user_id' => $user->id,
        'value' => ['value' => $validated['value']],
        'status' => PredictionStatus::Submitted,
        'submitted_at' => now(),
    ]);

    expect($prediction->status)->toBe(PredictionStatus::Submitted);
});

test('duplicate submission is prevented by uniqueness constraints', function () {
    $field = PredictionField::factory()->create();
    $user = User::factory()->create();

    Prediction::query()->create([
        'tournament_id' => $field->tournament_id,
        'prediction_field_id' => $field->id,
        'user_id' => $user->id,
        'value' => ['value' => 'A'],
        'status' => PredictionStatus::Submitted,
        'submitted_at' => now(),
    ]);

    expect(fn () => Prediction::query()->create([
        'tournament_id' => $field->tournament_id,
        'prediction_field_id' => $field->id,
        'user_id' => $user->id,
        'value' => ['value' => 'B'],
        'status' => PredictionStatus::Submitted,
        'submitted_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('prediction result creation is separated from user predictions', function () {
    $field = PredictionField::factory()->create();

    $result = PredictionResult::query()->create([
        'tournament_id' => $field->tournament_id,
        'prediction_field_id' => $field->id,
        'value' => ['value' => 'Argentina'],
        'status' => PredictionResultStatus::Resolved,
        'resolved_at' => now(),
    ]);

    expect($result->predictionField->is($field))->toBeTrue();
    expect(Prediction::query()->count())->toBe(0);
});

test('prediction score is persisted by scoring service', function () {
    $field = PredictionField::factory()->create([
        'configuration' => ['max_points' => 3],
        'scoring_strategy_key' => 'exact_match',
    ]);

    $prediction = Prediction::factory()->forField($field)->create([
        'value' => ['value' => 'Brazil'],
    ]);

    $result = PredictionResult::factory()->create([
        'tournament_id' => $field->tournament_id,
        'prediction_field_id' => $field->id,
        'value' => ['value' => 'Brazil'],
        'status' => PredictionResultStatus::Resolved,
    ]);

    $score = app(PredictionScoringService::class)->scorePrediction($prediction, $result);

    expect($score->points)->toBe(3);

    $this->assertDatabaseHas('prediction_scores', [
        'prediction_id' => $prediction->id,
        'points' => 3,
        'max_points' => 3,
    ]);
});

test('exact match strategy scores correctly', function () {
    $field = PredictionField::factory()->create([
        'configuration' => ['max_points' => 2],
    ]);

    $matchingPrediction = Prediction::factory()->forField($field)->create([
        'value' => ['value' => 'Sweden'],
    ]);

    $nonMatchingPrediction = Prediction::factory()->forField($field)->create([
        'user_id' => User::factory()->create()->id,
        'value' => ['value' => 'Finland'],
    ]);

    $result = PredictionResult::factory()->create([
        'tournament_id' => $field->tournament_id,
        'prediction_field_id' => $field->id,
        'value' => ['value' => 'Sweden'],
        'status' => PredictionResultStatus::Resolved,
    ]);

    $strategy = new ExactMatchStrategy;

    expect($strategy->calculate($matchingPrediction, $result)->points)->toBe(2);
    expect($strategy->calculate($nonMatchingPrediction, $result)->points)->toBe(0);
});

test('prediction locking prevents updates after match lock time', function () {
    $team = Team::factory()->create();
    $tournament = Tournament::query()->findOrFail($team->id);
    $home = TournamentTeam::factory()->forTournament($tournament)->create();
    $away = TournamentTeam::factory()->forTournament($tournament)->create();
    $match = TournamentMatch::factory()->forTeams($tournament, $home, $away)->create([
        'locks_at' => now()->subMinute(),
    ]);

    $field = PredictionField::factory()->forTournament($tournament)->matchScoped()->create([
        'configuration' => ['is_locked' => false],
    ]);

    $prediction = Prediction::factory()->forField($field)->forMatch($match)->create([
        'status' => PredictionStatus::Submitted,
    ]);

    $lockService = app(PredictionLockService::class);
    $lockService->syncPredictionLockState($prediction);

    $prediction->refresh();

    expect($prediction->isLocked())->toBeTrue();

    expect(function () use ($prediction): void {
        $prediction->update(['value' => ['value' => 'Updated']]);
    })->toThrow(InvalidArgumentException::class);
});

test('visibility enum behavior supports lock and result gates', function () {
    expect(PredictionVisibility::AlwaysVisible->shouldReveal(false, false))->toBeTrue();
    expect(PredictionVisibility::HiddenUntilLock->shouldReveal(false, true))->toBeFalse();
    expect(PredictionVisibility::HiddenUntilLock->shouldReveal(true, false))->toBeTrue();
    expect(PredictionVisibility::HiddenUntilResult->shouldReveal(true, false))->toBeFalse();
    expect(PredictionVisibility::HiddenUntilResult->shouldReveal(false, true))->toBeTrue();
});

test('tournament scoped field can be created without match', function () {
    $field = PredictionField::factory()->tournamentScoped()->create();

    expect($field->scope)->toBe(PredictionScope::Tournament);
    expect($field->predictionResults)->toHaveCount(0);
});

test('match scoped field is reusable across matches', function () {
    $team = Team::factory()->create();
    $tournament = Tournament::query()->findOrFail($team->id);
    $home = TournamentTeam::factory()->forTournament($tournament)->create();
    $away = TournamentTeam::factory()->forTournament($tournament)->create();
    $secondHome = TournamentTeam::factory()->forTournament($tournament)->create();
    $secondAway = TournamentTeam::factory()->forTournament($tournament)->create();
    $firstMatch = TournamentMatch::factory()->forTeams($tournament, $home, $away)->create();
    $secondMatch = TournamentMatch::factory()->forTeams($tournament, $secondHome, $secondAway)->create();

    $field = PredictionField::factory()->forTournament($tournament)->matchScoped()->create();

    $user = User::factory()->create();

    $firstPrediction = Prediction::factory()->forField($field)->forMatch($firstMatch)->create([
        'user_id' => $user->id,
    ]);

    $secondPrediction = Prediction::factory()->forField($field)->forMatch($secondMatch)->create([
        'user_id' => $user->id,
    ]);

    expect($field->scope)->toBe(PredictionScope::Match);
    expect($field->predictions)->toHaveCount(2);
    expect($firstPrediction->tournament_match_id)->toBe($firstMatch->id);
    expect($secondPrediction->tournament_match_id)->toBe($secondMatch->id);
});

test('match scoped results are stored per field and per match', function () {
    $team = Team::factory()->create();
    $tournament = Tournament::query()->findOrFail($team->id);
    $home = TournamentTeam::factory()->forTournament($tournament)->create();
    $away = TournamentTeam::factory()->forTournament($tournament)->create();
    $secondHome = TournamentTeam::factory()->forTournament($tournament)->create();
    $secondAway = TournamentTeam::factory()->forTournament($tournament)->create();
    $firstMatch = TournamentMatch::factory()->forTeams($tournament, $home, $away)->create();
    $secondMatch = TournamentMatch::factory()->forTeams($tournament, $secondHome, $secondAway)->create();

    $field = PredictionField::factory()->forTournament($tournament)->matchScoped()->create();

    PredictionResult::factory()->forMatch($firstMatch)->create([
        'prediction_field_id' => $field->id,
    ]);

    PredictionResult::factory()->forMatch($secondMatch)->create([
        'prediction_field_id' => $field->id,
    ]);

    expect($field->predictionResults()->count())->toBe(2);
});

test('dashboard exposes prediction field admin payload', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'World Cup 2026']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $tournament = Tournament::query()->findOrFail($team->id);

    $field = PredictionField::factory()->forTournament($tournament)->create([
        'label' => 'Champion',
        'key' => 'champion',
    ]);

    PredictionResult::factory()->create([
        'tournament_id' => $tournament->id,
        'prediction_field_id' => $field->id,
        'status' => PredictionResultStatus::Resolved,
        'value' => ['value' => 'Brazil'],
        'resolved_by' => $owner->id,
        'resolved_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->has('predictionFields', 1)
            ->where('predictionFields.0.key', 'champion')
            ->where('predictionFields.0.results.0.status', PredictionResultStatus::Resolved->value)
            ->where('canManagePredictionFields', true)
            ->where('canResolvePredictionResults', true),
        );
});
