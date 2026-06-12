<?php

namespace App\Domain\Tournaments\Scoring;

class ScoreResult
{
    /**
     * @param  array<string, mixed>  $breakdown
     */
    public function __construct(
        public readonly int $points,
        public readonly int $maxPoints,
        public readonly array $breakdown = [],
    ) {}
}
