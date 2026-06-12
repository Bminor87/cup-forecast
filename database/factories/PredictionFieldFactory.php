<?php

namespace Database\Factories;

use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionOptionSource;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Enums\PredictionVisibility;
use App\Domain\Tournaments\Enums\TournamentCompetitionMode;
use App\Domain\Tournaments\Enums\TournamentSportType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PredictionField>
 */
class PredictionFieldFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PredictionField>
     */
    protected $model = PredictionField::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tournament = Tournament::query()->create([
            'name' => fake()->unique()->sentence(3),
            'sport_type' => TournamentSportType::Football,
            'competition_mode' => TournamentCompetitionMode::NationalTeams,
            'status' => TournamentStatus::Draft,
        ]);

        return [
            'tournament_id' => $tournament->id,
            'scope' => PredictionScope::Tournament,
            'field_type' => PredictionFieldType::Text,
            'label' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'key' => fake()->unique()->slug(2),
            'visibility' => PredictionVisibility::HiddenUntilResult,
            'validation_schema' => ['required' => true],
            'scoring_strategy_key' => 'exact_match',
            'configuration' => ['is_locked' => false, 'max_points' => 1],
            'is_active' => true,
        ];
    }

    public function forTournament(Tournament $tournament): static
    {
        return $this->state(fn (): array => [
            'tournament_id' => $tournament->id,
        ]);
    }

    public function tournamentScoped(): static
    {
        return $this->state(fn (): array => [
            'scope' => PredictionScope::Tournament,
        ]);
    }

    public function matchScoped(): static
    {
        return $this->state(fn (): array => [
            'scope' => PredictionScope::Match,
        ]);
    }

    public function teamPicker(?PredictionOptionSource $optionSource = null): static
    {
        $optionSource ??= PredictionOptionSource::AllTournamentTeams;

        return $this->state(fn (): array => [
            'field_type' => PredictionFieldType::TeamPicker,
            'configuration' => [
                'is_locked' => false,
                'max_points' => 1,
                'option_source' => $optionSource->value,
            ],
        ]);
    }

    public function playerPicker(?PredictionOptionSource $optionSource = null): static
    {
        $optionSource ??= PredictionOptionSource::AllTournamentPlayers;

        return $this->state(fn (): array => [
            'field_type' => PredictionFieldType::PlayerPicker,
            'configuration' => [
                'is_locked' => false,
                'max_points' => 1,
                'option_source' => $optionSource->value,
            ],
        ]);
    }
}
