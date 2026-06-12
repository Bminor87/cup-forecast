<?php

use App\Domain\Tournaments\Enums\TeamType;
use App\Domain\Tournaments\Enums\TournamentCompetitionMode;
use App\Domain\Tournaments\Enums\TournamentSportType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Enums\TeamRole;
use App\Models\User;
use Database\Seeders\TournamentTeamSeeder;
use Illuminate\Support\Facades\Gate;

test('tournament has many tournament teams and team belongs to tournament', function () {
    $tournament = Tournament::query()->create([
        'name' => 'Euro 2028',
        'sport_type' => TournamentSportType::Football,
        'competition_mode' => TournamentCompetitionMode::NationalTeams,
        'status' => TournamentStatus::Draft,
    ]);

    $tournamentTeam = TournamentTeam::factory()
        ->forTournament($tournament)
        ->national()
        ->create();

    expect($tournament->tournamentTeams()->count())->toBe(1);
    expect($tournamentTeam->tournament->is($tournament))->toBeTrue();
    expect($tournamentTeam->type)->toBe(TeamType::National);
});

test('tournament team policy allows admin and owner but blocks members', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();

    $tournament = Tournament::query()->create([
        'name' => 'Hockey Worlds 2028',
        'sport_type' => TournamentSportType::IceHockey,
        'competition_mode' => TournamentCompetitionMode::NationalTeams,
        'status' => TournamentStatus::Active,
    ]);

    $tournament->participants()->attach($owner, ['role' => TeamRole::Owner->value]);
    $tournament->participants()->attach($admin, ['role' => TeamRole::Admin->value]);
    $tournament->participants()->attach($member, ['role' => TeamRole::Member->value]);

    $tournamentTeam = TournamentTeam::factory()->forTournament($tournament)->create();

    expect(Gate::forUser($owner)->allows('create', [TournamentTeam::class, $tournament]))->toBeTrue();
    expect(Gate::forUser($admin)->allows('update', $tournamentTeam))->toBeTrue();
    expect(Gate::forUser($member)->allows('delete', $tournamentTeam))->toBeFalse();
});

test('tournament team seeder creates sample national teams once', function () {
    $this->seed(TournamentTeamSeeder::class);
    $this->seed(TournamentTeamSeeder::class);

    $tournament = Tournament::query()->where('slug', 'sample-football-world-cup')->first();

    expect($tournament)->not->toBeNull();
    expect($tournament?->tournamentTeams()->count())->toBe(8);
    expect(TournamentTeam::query()->where('type', TeamType::National->value)->count())->toBe(8);
});
