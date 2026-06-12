<?php

namespace Database\Seeders;

use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionOptionSource;
use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Enums\PredictionStatus;
use App\Domain\Tournaments\Enums\PredictionVisibility;
use App\Domain\Tournaments\Models\Player;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Scoring\PredictionScoringService;
use Illuminate\Database\Seeder;

class PredictionSeeder extends Seeder
{
    /**
     * Seed prediction engine records for participant and admin workflows.
     */
    public function run(): void
    {
        $tournament = Tournament::query()->where('slug', 'sample-football-world-cup')->first();

        if (! $tournament) {
            return;
        }

        $tournament->loadMissing(['tournamentTeams', 'players', 'matches', 'participants']);

        if ($tournament->tournamentTeams->isEmpty()) {
            return;
        }

        $teamId = $tournament->tournamentTeams->first()->id;
        $playerId = $tournament->players->first()?->id
            ?? Player::factory()->forTournamentTeam($tournament, $tournament->tournamentTeams->first())->create()->id;

        $tournamentFieldBlueprints = [
            [
                'key' => 'tournament_winner',
                'label' => 'Tournament winner',
                'field_type' => PredictionFieldType::TeamPicker,
                'validation_schema' => ['required' => true],
                'sample_value' => $teamId,
            ],
            [
                'key' => 'golden_boot',
                'label' => 'Golden Boot',
                'field_type' => PredictionFieldType::PlayerPicker,
                'validation_schema' => ['required' => true],
                'sample_value' => $playerId,
            ],
            [
                'key' => 'golden_glove',
                'label' => 'Golden Glove',
                'field_type' => PredictionFieldType::Text,
                'validation_schema' => ['required' => true, 'min' => 2],
                'sample_value' => 'Best Keeper',
            ],
            [
                'key' => 'total_goals',
                'label' => 'Tournament total goals',
                'field_type' => PredictionFieldType::Number,
                'validation_schema' => ['required' => true, 'min' => 1],
                'sample_value' => 120,
            ],
            [
                'key' => 'host_wins_final',
                'label' => 'Host wins final',
                'field_type' => PredictionFieldType::Boolean,
                'validation_schema' => ['required' => true],
                'sample_value' => false,
            ],
            [
                'key' => 'final_date_prediction',
                'label' => 'Final date prediction',
                'field_type' => PredictionFieldType::Date,
                'validation_schema' => ['required' => true],
                'sample_value' => now()->addDays(14)->toDateString(),
            ],
            [
                'key' => 'opening_goal_time',
                'label' => 'Opening goal time',
                'field_type' => PredictionFieldType::Time,
                'validation_schema' => ['required' => true],
                'sample_value' => '00:37',
            ],
        ];

        $matchFieldBlueprints = [
            [
                'key' => 'match_winner',
                'label' => 'Match winner',
                'field_type' => PredictionFieldType::TeamPicker,
                'validation_schema' => ['required' => true],
                'sample_value' => $teamId,
            ],
            [
                'key' => 'exact_score',
                'label' => 'Exact score',
                'field_type' => PredictionFieldType::Text,
                'validation_schema' => ['required' => true],
                'sample_value' => '2-1',
            ],
            [
                'key' => 'match_mvp',
                'label' => 'MVP',
                'field_type' => PredictionFieldType::PlayerPicker,
                'validation_schema' => ['required' => true],
                'sample_value' => $playerId,
            ],
        ];

        $allFields = collect();

        foreach ($tournamentFieldBlueprints as $blueprint) {
            $allFields->push($this->seedField($tournament->id, PredictionScope::Tournament, $blueprint));
        }

        foreach ($matchFieldBlueprints as $blueprint) {
            $allFields->push($this->seedField($tournament->id, PredictionScope::Match, $blueprint));
        }

        foreach ($tournament->participants as $participant) {
            foreach ($allFields as $field) {
                if ($field->scope === PredictionScope::Tournament) {
                    $contextKey = Prediction::tournamentContextKey();

                    Prediction::query()->updateOrCreate(
                        [
                            'tournament_id' => $tournament->id,
                            'prediction_field_id' => $field->id,
                            'user_id' => $participant->id,
                            'context_key' => $contextKey,
                        ],
                        [
                            'tournament_match_id' => null,
                            'value' => ['value' => $this->sampleValueFor($field, $tournament)],
                            'status' => PredictionStatus::Submitted,
                            'submitted_at' => now(),
                        ],
                    );

                    continue;
                }

                foreach ($tournament->matches as $match) {
                    $contextKey = Prediction::contextKeyForMatch($match->id);

                    Prediction::query()->updateOrCreate(
                        [
                            'tournament_id' => $tournament->id,
                            'prediction_field_id' => $field->id,
                            'user_id' => $participant->id,
                            'context_key' => $contextKey,
                        ],
                        [
                            'tournament_match_id' => $match->id,
                            'value' => ['value' => $this->sampleValueFor($field, $tournament)],
                            'status' => PredictionStatus::Submitted,
                            'submitted_at' => now(),
                        ],
                    );
                }
            }
        }

        $resolver = $tournament->participants->first();

        if (! $resolver) {
            return;
        }

        foreach ($allFields as $field) {
            if ($field->scope === PredictionScope::Tournament) {
                $result = PredictionResult::query()->updateOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'prediction_field_id' => $field->id,
                        'context_key' => Prediction::tournamentContextKey(),
                    ],
                    [
                        'tournament_match_id' => null,
                        'value' => ['value' => $this->sampleValueFor($field, $tournament)],
                        'status' => PredictionResultStatus::Resolved,
                        'resolved_by' => $resolver->id,
                        'resolved_at' => now(),
                    ],
                );

                app(PredictionScoringService::class)->scoreResolvedField($field, $result);

                continue;
            }

            foreach ($tournament->matches as $match) {
                $result = PredictionResult::query()->updateOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'prediction_field_id' => $field->id,
                        'context_key' => Prediction::contextKeyForMatch($match->id),
                    ],
                    [
                        'tournament_match_id' => $match->id,
                        'value' => ['value' => $this->sampleValueFor($field, $tournament)],
                        'status' => PredictionResultStatus::Resolved,
                        'resolved_by' => $resolver->id,
                        'resolved_at' => now(),
                    ],
                );

                app(PredictionScoringService::class)->scoreResolvedField($field, $result);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $blueprint
     */
    private function seedField(int $tournamentId, PredictionScope $scope, array $blueprint): PredictionField
    {
        /** @var PredictionField $field */
        $field = PredictionField::query()->updateOrCreate(
            [
                'tournament_id' => $tournamentId,
                'key' => $blueprint['key'],
            ],
            [
                'scope' => $scope,
                'field_type' => $blueprint['field_type'],
                'label' => $blueprint['label'],
                'description' => $blueprint['label'].' prediction',
                'visibility' => PredictionVisibility::AlwaysVisible,
                'validation_schema' => $blueprint['validation_schema'],
                'scoring_strategy_key' => 'exact_match',
                'configuration' => [
                    'is_locked' => false,
                    'max_points' => 3,
                    'option_source' => $this->defaultOptionSource($scope, $blueprint['field_type'])?->value,
                ],
                'is_active' => true,
            ],
        );

        return $field;
    }

    private function sampleValueFor(PredictionField $field, Tournament $tournament): mixed
    {
        return match ($field->field_type) {
            PredictionFieldType::TeamPicker => $tournament->tournamentTeams->first()?->id,
            PredictionFieldType::PlayerPicker => $tournament->players->first()?->id,
            PredictionFieldType::Text => 'Sample text',
            PredictionFieldType::Number => 2,
            PredictionFieldType::Boolean => true,
            PredictionFieldType::Date => now()->addWeek()->toDateString(),
            PredictionFieldType::Time => '01:15',
        };
    }

    private function defaultOptionSource(
        PredictionScope $scope,
        PredictionFieldType $fieldType,
    ): ?PredictionOptionSource {
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
