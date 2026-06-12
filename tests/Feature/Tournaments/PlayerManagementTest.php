<?php

use App\Domain\Tournaments\Enums\PlayerPosition;
use App\Domain\Tournaments\Enums\TournamentCompetitionMode;
use App\Domain\Tournaments\Enums\TournamentSportType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Models\Player;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Enums\TeamRole;
use App\Models\User;
use Database\Seeders\PlayerSeeder;
use Database\Seeders\TournamentTeamSeeder;
use Illuminate\Support\Facades\Gate;

test('player belongs to tournament and tournament team', function () {
    $tournament = Tournament::query()->create([
        'name' => 'World Cup 2030',
        'sport_type' => TournamentSportType::Football,
        'competition_mode' => TournamentCompetitionMode::NationalTeams,
        'status' => TournamentStatus::Active,
    ]);

    $tournamentTeam = TournamentTeam::factory()->forTournament($tournament)->create();

    $player = Player::factory()->forTournamentTeam($tournament, $tournamentTeam)->create();

    expect($player->tournament->is($tournament))->toBeTrue();
    expect($player->tournamentTeam->is($tournamentTeam))->toBeTrue();
    expect($tournament->players()->count())->toBe(1);
    expect($tournamentTeam->players()->count())->toBe(1);
});

test('player policy allows admin and owner but blocks members', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();

    $tournament = Tournament::query()->create([
        'name' => 'Hockey Worlds 2029',
        'sport_type' => TournamentSportType::IceHockey,
        'competition_mode' => TournamentCompetitionMode::NationalTeams,
        'status' => TournamentStatus::Active,
    ]);

    $tournament->participants()->attach($owner, ['role' => TeamRole::Owner->value]);
    $tournament->participants()->attach($admin, ['role' => TeamRole::Admin->value]);
    $tournament->participants()->attach($member, ['role' => TeamRole::Member->value]);

    $tournamentTeam = TournamentTeam::factory()->forTournament($tournament)->create();
    $player = Player::factory()->forTournamentTeam($tournament, $tournamentTeam)->create();

    expect(Gate::forUser($owner)->allows('create', [Player::class, $tournament]))->toBeTrue();
    expect(Gate::forUser($admin)->allows('update', $player))->toBeTrue();
    expect(Gate::forUser($member)->allows('delete', $player))->toBeFalse();
});

test('player supports unknown position for non-sport-specific future use', function () {
    $player = Player::factory()->unknownPosition()->create();

    expect($player->position)->toBe(PlayerPosition::Unknown);
});

test('player seeder creates players for seeded tournament teams once', function () {
    $this->seed(TournamentTeamSeeder::class);
    $this->seed(PlayerSeeder::class);
    $this->seed(PlayerSeeder::class);

    $tournament = Tournament::query()->where('slug', 'sample-football-world-cup')->firstOrFail();

    expect($tournament->players()->count())->toBe(40);
});
