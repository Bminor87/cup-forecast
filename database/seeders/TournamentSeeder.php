<?php

namespace Database\Seeders;

use App\Domain\Tournaments\Enums\TournamentCompetitionMode;
use App\Domain\Tournaments\Enums\TournamentSportType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Models\Tournament;
use App\Enums\TeamRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TournamentSeeder extends Seeder
{
    /**
     * Seed a reusable tournament with member accounts.
     */
    public function run(): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@example.com'],
            ['name' => 'Tournament Owner',
                'workos_id' => 'fake-'.Str::random(10),
                'avatar' => 'https://www.gravatar.com/avatar/'.Str::random(32).'?d=identicon'],
        );

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Tournament Admin', 'workos_id' => 'fake-'.Str::random(10), 'avatar' => 'https://www.gravatar.com/avatar/'.Str::random(32).'?d=identicon'],
        );

        $member = User::query()->firstOrCreate(
            ['email' => 'member@example.com'],
            ['name' => 'Tournament Member', 'workos_id' => 'fake-'.Str::random(10), 'avatar' => 'https://www.gravatar.com/avatar/'.Str::random(32).'?d=identicon'],
        );

        $tournament = Tournament::query()->firstOrCreate(
            ['slug' => 'sample-football-world-cup'],
            [
                'name' => 'Sample Football World Cup',
                'sport_type' => TournamentSportType::Football,
                'competition_mode' => TournamentCompetitionMode::NationalTeams,
                'status' => TournamentStatus::Active,
                'starts_at' => now()->addDays(10),
                'timezone' => 'UTC',
            ],
        );

        $tournament->participants()->syncWithoutDetaching([
            $owner->id => ['role' => TeamRole::Owner->value],
            $admin->id => ['role' => TeamRole::Admin->value],
            $member->id => ['role' => TeamRole::Member->value],
        ]);

        $owner->forceFill(['current_team_id' => $tournament->id])->save();
        $admin->forceFill(['current_team_id' => $tournament->id])->save();
        $member->forceFill(['current_team_id' => $tournament->id])->save();
    }
}
