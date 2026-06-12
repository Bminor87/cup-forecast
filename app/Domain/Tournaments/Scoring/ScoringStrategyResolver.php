<?php

namespace App\Domain\Tournaments\Scoring;

use InvalidArgumentException;

class ScoringStrategyResolver
{
    /**
     * @var array<string, class-string<ScoringStrategyInterface>>
     */
    protected array $strategies = [
        'exact_match' => ExactMatchStrategy::class,
    ];

    public function resolve(string $strategyKey): ScoringStrategyInterface
    {
        $strategyClass = $this->strategies[$strategyKey] ?? null;

        if ($strategyClass === null) {
            throw new InvalidArgumentException("Unknown scoring strategy key [{$strategyKey}].");
        }

        return app($strategyClass);
    }

    public function has(string $strategyKey): bool
    {
        return array_key_exists($strategyKey, $this->strategies);
    }
}
