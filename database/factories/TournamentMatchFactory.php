<?php

namespace Database\Factories;

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentMatch>
 */
class TournamentMatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TournamentMatch>
     */
    protected $model = TournamentMatch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $homeTeam = TournamentTeam::factory()->create();
        $awayTeam = TournamentTeam::factory()->forTournament($homeTeam->tournament)->create();

        return [
            'tournament_id' => $homeTeam->tournament_id,
            'home_tournament_team_id' => $homeTeam->id,
            'away_tournament_team_id' => $awayTeam->id,
            'starts_at' => now()->addDays(fake()->numberBetween(1, 14)),
            'locks_at' => now()->addDays(fake()->numberBetween(1, 14))->subHour(),
            'status' => MatchStatus::Scheduled,
            'venue' => fake()->city(),
            'home_score' => null,
            'away_score' => null,
            'metadata' => [
                'round' => fake()->randomElement(['group', 'quarterfinal', 'semifinal']),
            ],
        ];
    }

    /**
     * Associate the match with specific teams and tournament.
     */
    public function forTeams(Tournament $tournament, TournamentTeam $homeTeam, TournamentTeam $awayTeam): static
    {
        return $this->state(fn (array $attributes): array => [
            'tournament_id' => $tournament->id,
            'home_tournament_team_id' => $homeTeam->id,
            'away_tournament_team_id' => $awayTeam->id,
        ]);
    }
}
