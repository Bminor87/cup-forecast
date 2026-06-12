<?php

namespace App\Domain\Tournaments\Scoring;

use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Enums\PredictionStatus;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\PredictionScore;
use App\Domain\Tournaments\Models\Tournament;

class PredictionScoringService
{
    public function __construct(
        protected ScoringStrategyResolver $resolver,
    ) {}

    public function scorePrediction(Prediction $prediction, PredictionResult $result): PredictionScore
    {
        $strategyKey = $prediction->predictionField->scoring_strategy_key;
        $strategy = $this->resolver->resolve($strategyKey);
        $scoreResult = $strategy->calculate($prediction, $result);

        /** @var PredictionScore $score */
        $score = PredictionScore::query()->updateOrCreate(
            ['prediction_id' => $prediction->id],
            [
                'tournament_id' => $prediction->tournament_id,
                'prediction_field_id' => $prediction->prediction_field_id,
                'user_id' => $prediction->user_id,
                'strategy_key' => $strategyKey,
                'points' => $scoreResult->points,
                'max_points' => $scoreResult->maxPoints,
                'breakdown' => $scoreResult->breakdown,
                'scored_at' => now(),
            ],
        );

        return $score;
    }

    public function scoreResolvedField(PredictionField $field, PredictionResult $result): void
    {
        $field->predictions()
            ->where('context_key', $result->context_key)
            ->whereIn('status', [
                PredictionStatus::Submitted->value,
                PredictionStatus::Locked->value,
            ])
            ->cursor()
            ->each(fn (Prediction $prediction) => $this->scorePrediction($prediction, $result));
    }

    public function clearScoresForFieldContext(PredictionField $field, string $contextKey): int
    {
        $predictionIds = Prediction::query()
            ->where('tournament_id', $field->tournament_id)
            ->where('prediction_field_id', $field->id)
            ->where('context_key', $contextKey)
            ->pluck('id');

        if ($predictionIds->isEmpty()) {
            return 0;
        }

        return PredictionScore::query()
            ->whereIn('prediction_id', $predictionIds)
            ->delete();
    }

    public function recalculateTournament(Tournament $tournament): void
    {
        PredictionScore::query()
            ->where('tournament_id', $tournament->id)
            ->delete();

        PredictionResult::query()
            ->with('predictionField')
            ->where('tournament_id', $tournament->id)
            ->where('status', PredictionResultStatus::Resolved->value)
            ->cursor()
            ->each(function (PredictionResult $result): void {
                if ($result->predictionField === null) {
                    return;
                }

                $this->scoreResolvedField($result->predictionField, $result);
            });
    }
}
