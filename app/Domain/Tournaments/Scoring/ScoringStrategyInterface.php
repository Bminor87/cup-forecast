<?php

namespace App\Domain\Tournaments\Scoring;

use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionResult;

interface ScoringStrategyInterface
{
    public function calculate(Prediction $prediction, PredictionResult $result): ScoreResult;
}
