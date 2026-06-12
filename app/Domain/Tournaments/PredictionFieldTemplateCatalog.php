<?php

namespace App\Domain\Tournaments;

use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionOptionSource;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Enums\PredictionVisibility;
use App\Domain\Tournaments\Models\Tournament;

class PredictionFieldTemplateCatalog
{
    /**
     * @return array<int, array{key: string, label: string, description: string, scope: string}>
     */
    public function templates(): array
    {
        return collect($this->definitions())
            ->map(fn (array $template, string $key): array => [
                'key' => $key,
                'label' => $template['label'],
                'description' => $template['description'],
                'scope' => $template['scope']->value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function payloadFor(Tournament $tournament, string $templateKey): array
    {
        $template = $this->definitions()[$templateKey] ?? null;

        if ($template === null) {
            abort(422, 'Unknown prediction field template.');
        }

        $configuration = [
            'is_locked' => false,
            'max_points' => 1,
        ];

        if ($template['option_source'] !== null) {
            $configuration['option_source'] = $template['option_source']->value;
        }

        if ($template['static_options'] !== null) {
            $configuration['static_options'] = $template['static_options'];
        }

        return [
            'scope' => $template['scope']->value,
            'field_type' => $template['field_type']->value,
            'label' => $template['label'],
            'description' => $template['description'],
            'key' => $this->nextKey($tournament, $template['key']),
            'visibility' => PredictionVisibility::HiddenUntilResult->value,
            'validation_schema' => $template['validation_schema'],
            'scoring_strategy_key' => 'exact_match',
            'configuration' => $configuration,
            'is_active' => true,
        ];
    }

    /**
     * @return array<string, array{key: string, label: string, description: string, scope: PredictionScope, field_type: PredictionFieldType, option_source: ?PredictionOptionSource, validation_schema: array<string, mixed>, static_options: ?array<int, mixed>}>
     */
    protected function definitions(): array
    {
        return [
            'tournament_winner' => [
                'key' => 'tournament_winner',
                'label' => 'Tournament Winner',
                'description' => 'Pick the team that will win the tournament.',
                'scope' => PredictionScope::Tournament,
                'field_type' => PredictionFieldType::TeamPicker,
                'option_source' => PredictionOptionSource::AllTournamentTeams,
                'validation_schema' => ['required' => true],
                'static_options' => null,
            ],
            'silver_medalist' => [
                'key' => 'silver_medalist',
                'label' => 'Silver Medalist',
                'description' => 'Pick the team that will finish second.',
                'scope' => PredictionScope::Tournament,
                'field_type' => PredictionFieldType::TeamPicker,
                'option_source' => PredictionOptionSource::AllTournamentTeams,
                'validation_schema' => ['required' => true],
                'static_options' => null,
            ],
            'bronze_medalist' => [
                'key' => 'bronze_medalist',
                'label' => 'Bronze Medalist',
                'description' => 'Pick the team that will finish third.',
                'scope' => PredictionScope::Tournament,
                'field_type' => PredictionFieldType::TeamPicker,
                'option_source' => PredictionOptionSource::AllTournamentTeams,
                'validation_schema' => ['required' => true],
                'static_options' => null,
            ],
            'golden_boot' => [
                'key' => 'golden_boot',
                'label' => 'Golden Boot',
                'description' => 'Pick the tournament top scorer.',
                'scope' => PredictionScope::Tournament,
                'field_type' => PredictionFieldType::PlayerPicker,
                'option_source' => PredictionOptionSource::AllTournamentPlayers,
                'validation_schema' => ['required' => true],
                'static_options' => null,
            ],
            'golden_glove' => [
                'key' => 'golden_glove',
                'label' => 'Golden Glove',
                'description' => 'Pick the best goalkeeper.',
                'scope' => PredictionScope::Tournament,
                'field_type' => PredictionFieldType::PlayerPicker,
                'option_source' => PredictionOptionSource::AllTournamentPlayers,
                'validation_schema' => ['required' => true],
                'static_options' => null,
            ],
            'best_player' => [
                'key' => 'best_player',
                'label' => 'Best Player',
                'description' => 'Pick the player of the tournament.',
                'scope' => PredictionScope::Tournament,
                'field_type' => PredictionFieldType::PlayerPicker,
                'option_source' => PredictionOptionSource::AllTournamentPlayers,
                'validation_schema' => ['required' => true],
                'static_options' => null,
            ],
            'match_winner' => [
                'key' => 'match_winner',
                'label' => 'Match Winner',
                'description' => 'Pick the winning team for each match.',
                'scope' => PredictionScope::Match,
                'field_type' => PredictionFieldType::TeamPicker,
                'option_source' => PredictionOptionSource::MatchTeams,
                'validation_schema' => ['required' => true],
                'static_options' => null,
            ],
            'exact_score' => [
                'key' => 'exact_score',
                'label' => 'Exact Score',
                'description' => 'Enter final score in format 2-1.',
                'scope' => PredictionScope::Match,
                'field_type' => PredictionFieldType::Text,
                'option_source' => null,
                'validation_schema' => [
                    'required' => true,
                    'regex' => '/^\\d{1,2}-\\d{1,2}$/',
                ],
                'static_options' => null,
            ],
            'mvp' => [
                'key' => 'mvp',
                'label' => 'MVP',
                'description' => 'Pick the most valuable player of the match.',
                'scope' => PredictionScope::Match,
                'field_type' => PredictionFieldType::PlayerPicker,
                'option_source' => PredictionOptionSource::MatchPlayers,
                'validation_schema' => ['required' => true],
                'static_options' => null,
            ],
            'first_goal_scorer' => [
                'key' => 'first_goal_scorer',
                'label' => 'First Goal Scorer',
                'description' => 'Pick the first goal scorer in the match.',
                'scope' => PredictionScope::Match,
                'field_type' => PredictionFieldType::PlayerPicker,
                'option_source' => PredictionOptionSource::MatchPlayers,
                'validation_schema' => ['required' => true],
                'static_options' => null,
            ],
        ];
    }

    protected function nextKey(Tournament $tournament, string $baseKey): string
    {
        $candidate = $baseKey;
        $counter = 2;

        while ($tournament->predictionFields()->where('key', $candidate)->exists()) {
            $candidate = $baseKey.'_'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
