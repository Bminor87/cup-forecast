<?php

namespace Database\Seeders;

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use Illuminate\Database\Seeder;

class TournamentMatchSeeder extends Seeder
{
    /**
     * Seed sample matches for the sample tournament.
     */
    public function run(): void
    {
        $tournament = Tournament::query()->where('slug', 'sample-football-world-cup')->first();

        if (! $tournament) {
            return;
        }

        $tournament->loadMissing('tournamentTeams');

        if ($tournament->tournamentTeams->count() < 2 || $tournament->matches()->exists()) {
            return;
        }

        $teams = $tournament->tournamentTeams->values();

        for ($i = 0; $i < $teams->count() - 1; $i += 2) {
            $homeTeam = $teams[$i];
            $awayTeam = $teams[$i + 1];

            TournamentMatch::factory()
                ->forTeams($tournament, $homeTeam, $awayTeam)
                ->state(fn (array $attributes): array => [
                    'status' => MatchStatus::Scheduled,
                ])
                ->create();
        }
    }
}
