<?php

namespace App\Jobs;

use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Scoring\PredictionScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScorePredictionResultJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $predictionResultId,
    ) {}

    public function handle(PredictionScoringService $scoringService): void
    {
        $result = PredictionResult::query()
            ->with('predictionField')
            ->find($this->predictionResultId);

        if ($result === null || $result->predictionField === null) {
            return;
        }

        if ($result->status !== PredictionResultStatus::Resolved) {
            $scoringService->clearScoresForFieldContext($result->predictionField, $result->context_key);

            return;
        }

        $scoringService->scoreResolvedField($result->predictionField, $result);
    }
}
