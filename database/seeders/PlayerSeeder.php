<?php

namespace Database\Seeders;

use App\Domain\Tournaments\Models\Player;
use App\Domain\Tournaments\Models\Tournament;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    /**
     * Seed players for sample tournament teams.
     */
    public function run(): void
    {
        $tournament = Tournament::query()->where('slug', 'sample-football-world-cup')->first();

        if (! $tournament) {
            return;
        }

        $tournament->loadMissing('tournamentTeams');

        foreach ($tournament->tournamentTeams as $tournamentTeam) {
            if ($tournamentTeam->players()->exists()) {
                continue;
            }

            Player::factory()
                ->count(5)
                ->forTournamentTeam($tournament, $tournamentTeam)
                ->create();
        }
    }
}
