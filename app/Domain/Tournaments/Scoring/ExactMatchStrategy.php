<?php

namespace App\Domain\Tournaments\Scoring;

use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionResult;

class ExactMatchStrategy implements ScoringStrategyInterface
{
    public function calculate(Prediction $prediction, PredictionResult $result): ScoreResult
    {
        $maxPoints = (int) ($prediction->predictionField->configuration['max_points'] ?? 1);

        $predictionValue = $this->normalize($prediction->value);
        $resultValue = $this->normalize($result->value);
        $matched = $predictionValue === $resultValue;

        return new ScoreResult(
            points: $matched ? $maxPoints : 0,
            maxPoints: $maxPoints,
            breakdown: [
                'matched' => $matched,
                'prediction' => $predictionValue,
                'result' => $resultValue,
            ],
        );
    }

    protected function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            ksort($value);

            foreach ($value as $key => $item) {
                $value[$key] = $this->normalize($item);
            }
        }

        return $value;
    }
}
