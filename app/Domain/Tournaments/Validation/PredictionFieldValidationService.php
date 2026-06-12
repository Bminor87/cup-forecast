<?php

namespace App\Domain\Tournaments\Validation;

use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Scoring\ScoringStrategyResolver;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PredictionFieldValidationService
{
    public function __construct(
        protected ScoringStrategyResolver $strategyResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function validateDefinition(array $attributes): void
    {
        $scope = PredictionScope::from((string) Arr::get($attributes, 'scope'));
        $fieldType = PredictionFieldType::from((string) Arr::get($attributes, 'field_type'));
        $strategyKey = (string) Arr::get($attributes, 'scoring_strategy_key');

        if (! $this->strategyResolver->has($strategyKey)) {
            throw ValidationException::withMessages([
                'scoring_strategy_key' => 'Unsupported scoring strategy key.',
            ]);
        }

        if (in_array($fieldType, [PredictionFieldType::TeamPicker, PredictionFieldType::PlayerPicker], true)) {
            $schema = Arr::get($attributes, 'validation_schema', []);

            if (! is_array($schema)) {
                throw ValidationException::withMessages([
                    'validation_schema' => 'Validation schema must be a JSON object.',
                ]);
            }
        }
    }
}
