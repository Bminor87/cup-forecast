<?php

namespace App\Http\Controllers\Tournaments;

use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Locking\PredictionLockService;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Scoring\PredictionScoringService;
use App\Domain\Tournaments\Validation\PredictionResultValidationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\ResolvePredictionResultRequest;
use App\Jobs\ScorePredictionResultJob;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PredictionResultController extends Controller
{
    public function upsert(
        ResolvePredictionResultRequest $request,
        Team $team,
        PredictionField $predictionField,
        PredictionResultValidationService $resultValidator,
        PredictionLockService $lockService,
        PredictionScoringService $scoringService,
    ): RedirectResponse {
        abort_if($predictionField->tournament_id !== $team->id, 404);

        Gate::authorize('update', [PredictionResult::class, $predictionField]);

        $payload = $request->validated();
        $validated = $resultValidator->validate($predictionField, [
            'value' => $payload['value'],
            'tournament_match_id' => $payload['tournament_match_id'] ?? null,
        ]);

        $contextKey = $predictionField->scope === PredictionScope::Match
            ? Prediction::contextKeyForMatch((int) $validated['tournament_match_id'])
            : Prediction::tournamentContextKey();

        /** @var PredictionResult $result */
        $result = PredictionResult::query()->updateOrCreate(
            [
                'tournament_id' => $team->id,
                'prediction_field_id' => $predictionField->id,
                'context_key' => $contextKey,
            ],
            [
                'tournament_match_id' => $validated['tournament_match_id'] ?? null,
                'value' => ['value' => $validated['value']],
                'status' => $payload['status'],
                'resolved_by' => $payload['status'] === PredictionResultStatus::Resolved->value
                    ? $request->user()->id
                    : null,
                'resolved_at' => $payload['status'] === PredictionResultStatus::Resolved->value
                    ? now()
                    : null,
            ],
        );

        $lockService->syncFieldPredictions($predictionField, $result->tournamentMatch);

        if ($result->status === PredictionResultStatus::Resolved) {
            ScorePredictionResultJob::dispatch($result->id);
        } else {
            $scoringService->clearScoresForFieldContext($predictionField, $result->context_key);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prediction result saved.')]);

        return back();
    }
}
