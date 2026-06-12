<?php

namespace Database\Seeders;

use App\Domain\Tournaments\Enums\TeamType;
use App\Domain\Tournaments\Enums\TournamentCompetitionMode;
use App\Domain\Tournaments\Enums\TournamentSportType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentTeam;
use Illuminate\Database\Seeder;

class TournamentTeamSeeder extends Seeder
{
    /**
     * Seed tournament teams for a sample tournament.
     */
    public function run(): void
    {
        $tournament = Tournament::query()->firstOrCreate(
            ['slug' => 'sample-football-world-cup'],
            [
                'name' => 'Sample Football World Cup',
                'sport_type' => TournamentSportType::Football,
                'competition_mode' => TournamentCompetitionMode::NationalTeams,
                'status' => TournamentStatus::Active,
            ],
        );

        if ($tournament->tournamentTeams()->exists()) {
            return;
        }

        TournamentTeam::factory()
            ->count(8)
            ->forTournament($tournament)
            ->state(fn (array $attributes): array => ['type' => TeamType::National])
            ->create();
    }
}
