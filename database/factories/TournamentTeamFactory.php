<?php

namespace Database\Factories;

use App\Domain\Tournaments\Enums\TeamType;
use App\Domain\Tournaments\Enums\TournamentCompetitionMode;
use App\Domain\Tournaments\Enums\TournamentSportType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentTeam>
 */
class TournamentTeamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TournamentTeam>
     */
    protected $model = TournamentTeam::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::query()->create([
                'name' => fake()->unique()->sentence(3),
                'sport_type' => TournamentSportType::Football,
                'competition_mode' => TournamentCompetitionMode::NationalTeams,
                'status' => TournamentStatus::Draft,
                'slug' => fake()->unique()->slug(),
            ])->id,
            'name' => fake()->unique()->country(),
            'short_name' => strtoupper(fake()->lexify('???')),
            'type' => TeamType::National,
            'external_ref' => fake()->uuid(),
            'metadata' => [
                'seed' => fake()->numberBetween(1, 64),
            ],
        ];
    }

    /**
     * Set team type to national.
     */
    public function national(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TeamType::National,
        ]);
    }

    /**
     * Set team type to club.
     */
    public function club(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TeamType::Club,
        ]);
    }

    /**
     * Associate the team with a specific tournament.
     */
    public function forTournament(Tournament $tournament): static
    {
        return $this->state(fn (array $attributes): array => [
            'tournament_id' => $tournament->id,
        ]);
    }
}
