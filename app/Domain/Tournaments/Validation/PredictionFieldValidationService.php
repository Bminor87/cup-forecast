<?php

namespace App\Domain\Tournaments\Validation;

use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionOptionSource;
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

        $configuration = Arr::get($attributes, 'configuration', []);

        if ($configuration !== null && ! is_array($configuration)) {
            throw ValidationException::withMessages([
                'configuration' => 'Configuration must be a JSON object.',
            ]);
        }

        $configuration = is_array($configuration) ? $configuration : [];

        $resolvedSource = $this->resolveOptionSource($fieldType, $scope, $configuration);

        if ($resolvedSource === PredictionOptionSource::MatchTeams && $fieldType !== PredictionFieldType::TeamPicker) {
            throw ValidationException::withMessages([
                'configuration.option_source' => 'match_teams is only valid for TeamPicker fields.',
            ]);
        }

        if ($resolvedSource === PredictionOptionSource::MatchPlayers && $fieldType !== PredictionFieldType::PlayerPicker) {
            throw ValidationException::withMessages([
                'configuration.option_source' => 'match_players is only valid for PlayerPicker fields.',
            ]);
        }

        if (in_array($resolvedSource, [PredictionOptionSource::MatchTeams, PredictionOptionSource::MatchPlayers], true)
            && $scope !== PredictionScope::Match) {
            throw ValidationException::withMessages([
                'configuration.option_source' => 'Match option sources can only be used with match-scoped fields.',
            ]);
        }

        if ($resolvedSource === PredictionOptionSource::AllTournamentTeams && $fieldType !== PredictionFieldType::TeamPicker) {
            throw ValidationException::withMessages([
                'configuration.option_source' => 'all_tournament_teams is only valid for TeamPicker fields.',
            ]);
        }

        if ($resolvedSource === PredictionOptionSource::AllTournamentPlayers && $fieldType !== PredictionFieldType::PlayerPicker) {
            throw ValidationException::withMessages([
                'configuration.option_source' => 'all_tournament_players is only valid for PlayerPicker fields.',
            ]);
        }

        if ($resolvedSource === PredictionOptionSource::StaticOptions) {
            $staticOptions = Arr::get($configuration, 'static_options', []);

            if (! is_array($staticOptions) || $staticOptions === []) {
                throw ValidationException::withMessages([
                    'configuration.static_options' => 'Static options source requires a non-empty static_options array.',
                ]);
            }

            foreach ($staticOptions as $index => $option) {
                if (is_scalar($option)) {
                    continue;
                }

                if (is_array($option) && array_key_exists('value', $option) && is_scalar($option['value'])) {
                    continue;
                }

                throw ValidationException::withMessages([
                    'configuration.static_options' => "Invalid static option at index {$index}.",
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    protected function resolveOptionSource(
        PredictionFieldType $fieldType,
        PredictionScope $scope,
        array $configuration,
    ): ?PredictionOptionSource {
        $configuredSource = Arr::get($configuration, 'option_source');

        if (is_string($configuredSource)) {
            $source = PredictionOptionSource::tryFrom($configuredSource);

            if ($source === null) {
                throw ValidationException::withMessages([
                    'configuration.option_source' => 'Unsupported option source.',
                ]);
            }

            return $source;
        }

        return match ($fieldType) {
            PredictionFieldType::TeamPicker => $scope === PredictionScope::Match
                ? PredictionOptionSource::MatchTeams
                : PredictionOptionSource::AllTournamentTeams,
            PredictionFieldType::PlayerPicker => $scope === PredictionScope::Match
                ? PredictionOptionSource::MatchPlayers
                : PredictionOptionSource::AllTournamentPlayers,
            default => null,
        };
    }
}
