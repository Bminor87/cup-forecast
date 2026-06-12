<?php

namespace Database\Factories;

use App\Domain\Tournaments\Enums\PlayerPosition;
use App\Domain\Tournaments\Models\Player;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Player>
     */
    protected $model = Player::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tournamentTeam = TournamentTeam::factory()->create();

        return [
            'tournament_id' => $tournamentTeam->tournament_id,
            'tournament_team_id' => $tournamentTeam->id,
            'name' => fake()->name(),
            'short_name' => fake()->firstName(),
            'shirt_number' => fake()->numberBetween(1, 99),
            'position' => fake()->randomElement(PlayerPosition::cases()),
            'external_ref' => fake()->uuid(),
            'image_url' => fake()->imageUrl(),
            'metadata' => [
                'country' => fake()->countryCode(),
            ],
        ];
    }

    /**
     * Associate player with a specific tournament and tournament team.
     */
    public function forTournamentTeam(Tournament $tournament, TournamentTeam $tournamentTeam): static
    {
        return $this->state(fn (array $attributes): array => [
            'tournament_id' => $tournament->id,
            'tournament_team_id' => $tournamentTeam->id,
        ]);
    }

    /**
     * Set player position to unknown for non-sport-specific rosters.
     */
    public function unknownPosition(): static
    {
        return $this->state(fn (array $attributes): array => [
            'position' => PlayerPosition::Unknown,
        ]);
    }
}
