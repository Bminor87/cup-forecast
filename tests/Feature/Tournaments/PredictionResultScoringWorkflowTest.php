<?php

use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Enums\PredictionStatus;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\PredictionScore;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Enums\TeamRole;
use App\Jobs\RecalculateTournamentPredictionScoresJob;
use App\Jobs\ScorePredictionResultJob;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

beforeEach(function () {
    $this->withoutMiddleware([
        ValidateSessionWithWorkOS::class,
    ]);
});

test('resolved tournament result upsert dispatches scoring job', function () {
    [$admin, $team, $tournament] = adminTournamentContext();

    $field = PredictionField::factory()->forTournament($tournament)->tournamentScoped()->create([
        'field_type' => PredictionFieldType::Text,
        'validation_schema' => ['required' => true],
    ]);

    Queue::fake();

    $this->actingAs($admin)
        ->withSession(['_token' => 'test-token'])
        ->put(route('teams.prediction-results.upsert', [
            'team' => $team->slug,
            'predictionField' => $field->id,
        ]), [
            '_token' => 'test-token',
            'result_value' => json_encode(['value' => 'Brazil']),
            'status' => PredictionResultStatus::Resolved->value,
        ])
        ->assertRedirect();

    $result = PredictionResult::query()
        ->where('prediction_field_id', $field->id)
        ->where('context_key', Prediction::tournamentContextKey())
        ->firstOrFail();

    expect($result->status)->toBe(PredictionResultStatus::Resolved);

    Queue::assertPushed(ScorePredictionResultJob::class, function (ScorePredictionResultJob $job) use ($result): bool {
        return $job->predictionResultId === $result->id;
    });
});

test('resolved match result upsert stores match context and dispatches scoring job', function () {
    [$admin, $team, $tournament] = adminTournamentContext();
    [$match] = scoringWorkflowMatchContext($tournament);

    $field = PredictionField::factory()->forTournament($tournament)->matchScoped()->create([
        'field_type' => PredictionFieldType::Text,
        'validation_schema' => ['required' => true],
    ]);

    Queue::fake();

    $this->actingAs($admin)
        ->withSession(['_token' => 'test-token'])
        ->put(route('teams.prediction-results.upsert', [
            'team' => $team->slug,
            'predictionField' => $field->id,
        ]), [
            '_token' => 'test-token',
            'result_value' => json_encode(['value' => '2-1']),
            'status' => PredictionResultStatus::Resolved->value,
            'tournament_match_id' => $match->id,
        ])
        ->assertRedirect();

    $contextKey = Prediction::contextKeyForMatch($match->id);

    $result = PredictionResult::query()
        ->where('prediction_field_id', $field->id)
        ->where('context_key', $contextKey)
        ->firstOrFail();

    expect($result->tournament_match_id)->toBe($match->id);

    Queue::assertPushed(ScorePredictionResultJob::class, function (ScorePredictionResultJob $job) use ($result): bool {
        return $job->predictionResultId === $result->id;
    });
});

test('score job persists exact match score records', function () {
    [, , $tournament] = adminTournamentContext();

    $field = PredictionField::factory()->forTournament($tournament)->tournamentScoped()->create([
        'field_type' => PredictionFieldType::Text,
        'configuration' => ['is_locked' => false, 'max_points' => 3],
        'scoring_strategy_key' => 'exact_match',
        'validation_schema' => ['required' => true],
    ]);

    $prediction = Prediction::factory()->forField($field)->create([
        'value' => ['value' => 'Brazil'],
        'status' => PredictionStatus::Submitted,
        'context_key' => Prediction::tournamentContextKey(),
    ]);

    $result = PredictionResult::factory()->create([
        'tournament_id' => $tournament->id,
        'prediction_field_id' => $field->id,
        'context_key' => Prediction::tournamentContextKey(),
        'status' => PredictionResultStatus::Resolved,
        'value' => ['value' => 'Brazil'],
        'resolved_at' => now(),
    ]);

    dispatch_sync(new ScorePredictionResultJob($result->id));

    $this->assertDatabaseHas('prediction_scores', [
        'prediction_id' => $prediction->id,
        'tournament_id' => $tournament->id,
        'prediction_field_id' => $field->id,
        'strategy_key' => 'exact_match',
        'points' => 3,
        'max_points' => 3,
    ]);
});

test('recalculate endpoint queues tournament score rebuild job', function () {
    [$admin, $team, $tournament] = adminTournamentContext();

    Queue::fake();

    $this->actingAs($admin)
        ->withSession(['_token' => 'test-token'])
        ->post(route('teams.prediction-scores.recalculate', ['team' => $team->slug]), [
            '_token' => 'test-token',
        ])
        ->assertRedirect();

    Queue::assertPushed(RecalculateTournamentPredictionScoresJob::class, function (RecalculateTournamentPredictionScoresJob $job) use ($tournament): bool {
        return $job->tournamentId === $tournament->id;
    });
});

test('recalculation job rebuilds scores after official result update', function () {
    [, , $tournament] = adminTournamentContext();

    $field = PredictionField::factory()->forTournament($tournament)->tournamentScoped()->create([
        'field_type' => PredictionFieldType::Text,
        'configuration' => ['is_locked' => false, 'max_points' => 2],
        'scoring_strategy_key' => 'exact_match',
        'validation_schema' => ['required' => true],
    ]);

    $prediction = Prediction::factory()->forField($field)->create([
        'value' => ['value' => 'A'],
        'status' => PredictionStatus::Submitted,
    ]);

    $result = PredictionResult::factory()->create([
        'tournament_id' => $tournament->id,
        'prediction_field_id' => $field->id,
        'context_key' => Prediction::tournamentContextKey(),
        'status' => PredictionResultStatus::Resolved,
        'value' => ['value' => 'A'],
        'resolved_at' => now(),
    ]);

    dispatch_sync(new ScorePredictionResultJob($result->id));

    $this->assertDatabaseHas('prediction_scores', [
        'prediction_id' => $prediction->id,
        'points' => 2,
    ]);

    $result->update([
        'value' => ['value' => 'B'],
        'status' => PredictionResultStatus::Resolved,
        'resolved_at' => now(),
    ]);

    dispatch_sync(new RecalculateTournamentPredictionScoresJob($tournament->id));

    $predictionScore = PredictionScore::query()->where('prediction_id', $prediction->id)->firstOrFail();

    expect($predictionScore->points)->toBe(0);
    expect($predictionScore->max_points)->toBe(2);
});

test('setting result status away from resolved clears persisted scores for that context', function () {
    [$admin, $team, $tournament] = adminTournamentContext();

    $field = PredictionField::factory()->forTournament($tournament)->tournamentScoped()->create([
        'field_type' => PredictionFieldType::Text,
        'configuration' => ['is_locked' => false, 'max_points' => 1],
        'scoring_strategy_key' => 'exact_match',
        'validation_schema' => ['required' => true],
    ]);

    $prediction = Prediction::factory()->forField($field)->create([
        'value' => ['value' => 'Brazil'],
        'status' => PredictionStatus::Submitted,
        'context_key' => Prediction::tournamentContextKey(),
    ]);

    $result = PredictionResult::factory()->create([
        'tournament_id' => $tournament->id,
        'prediction_field_id' => $field->id,
        'context_key' => Prediction::tournamentContextKey(),
        'status' => PredictionResultStatus::Resolved,
        'value' => ['value' => 'Brazil'],
        'resolved_at' => now(),
    ]);

    dispatch_sync(new ScorePredictionResultJob($result->id));

    $this->assertDatabaseHas('prediction_scores', [
        'prediction_id' => $prediction->id,
        'points' => 1,
    ]);

    Queue::fake();

    $this->actingAs($admin)
        ->withSession(['_token' => 'test-token'])
        ->put(route('teams.prediction-results.upsert', [
            'team' => $team->slug,
            'predictionField' => $field->id,
        ]), [
            '_token' => 'test-token',
            'result_value' => json_encode(['value' => 'Brazil']),
            'status' => PredictionResultStatus::Pending->value,
        ])
        ->assertRedirect();

    Queue::assertNotPushed(ScorePredictionResultJob::class);

    $this->assertDatabaseMissing('prediction_scores', [
        'prediction_id' => $prediction->id,
    ]);
});

/**
 * @return array{0: User, 1: Team, 2: Tournament}
 */
function adminTournamentContext(): array
{
    $admin = User::factory()->create();
    $team = Team::factory()->create();

    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $admin->forceFill(['current_team_id' => $team->id])->save();

    $tournament = Tournament::query()->findOrFail($team->id);

    return [$admin, $team, $tournament];
}

/**
 * @return array{0: TournamentMatch, 1: TournamentTeam, 2: TournamentTeam}
 */
function scoringWorkflowMatchContext(Tournament $tournament): array
{
    $home = TournamentTeam::factory()->forTournament($tournament)->create();
    $away = TournamentTeam::factory()->forTournament($tournament)->create();

    $match = TournamentMatch::factory()->forTeams($tournament, $home, $away)->create([
        'starts_at' => now()->addHour(),
        'locks_at' => now()->addMinutes(30),
    ]);

    return [$match, $home, $away];
}
