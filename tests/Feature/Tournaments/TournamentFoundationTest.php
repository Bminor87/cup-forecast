<?php

use App\Domain\Tournaments\Enums\TournamentCompetitionMode;
use App\Domain\Tournaments\Enums\TournamentSportType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Models\Tournament;
use App\Enums\TeamRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('tournament persists to teams table with tournament defaults', function () {
    $tournament = Tournament::query()->create([
        'name' => 'World Cup 2026',
        'sport_type' => TournamentSportType::Football,
        'competition_mode' => TournamentCompetitionMode::NationalTeams,
        'status' => TournamentStatus::Draft,
        'timezone' => 'Europe/Helsinki',
        'settings' => ['is_public' => true],
    ]);

    $this->assertDatabaseHas('teams', [
        'id' => $tournament->id,
        'name' => 'World Cup 2026',
        'is_personal' => false,
        'sport_type' => TournamentSportType::Football->value,
        'competition_mode' => TournamentCompetitionMode::NationalTeams->value,
        'status' => TournamentStatus::Draft->value,
        'timezone' => 'Europe/Helsinki',
    ]);

    expect($tournament->slug)->not->toBeEmpty();
    expect($tournament->sport_type)->toBe(TournamentSportType::Football);
    expect($tournament->competition_mode)->toBe(TournamentCompetitionMode::NationalTeams);
    expect($tournament->status)->toBe(TournamentStatus::Draft);
    expect($tournament->settings)->toBe(['is_public' => true]);
});

test('tournament participants relation uses team members table', function () {
    $owner = User::factory()->create();
    $participant = User::factory()->create();

    $tournament = Tournament::query()->create([
        'name' => 'Euro 2028',
        'sport_type' => TournamentSportType::Football,
        'competition_mode' => TournamentCompetitionMode::NationalTeams,
        'status' => TournamentStatus::Active,
    ]);

    $tournament->participants()->attach($owner, ['role' => TeamRole::Owner->value]);
    $tournament->participants()->attach($participant, ['role' => TeamRole::Member->value]);

    expect($tournament->participants()->count())->toBe(2);
    expect($tournament->owner()?->is($owner))->toBeTrue();
});

test('tournament policy authorizes using participant role hierarchy', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();

    $tournament = Tournament::query()->create([
        'name' => 'Hockey Worlds 2027',
        'sport_type' => TournamentSportType::IceHockey,
        'competition_mode' => TournamentCompetitionMode::NationalTeams,
        'status' => TournamentStatus::Active,
    ]);

    $tournament->participants()->attach($owner, ['role' => TeamRole::Owner->value]);
    $tournament->participants()->attach($admin, ['role' => TeamRole::Admin->value]);
    $tournament->participants()->attach($member, ['role' => TeamRole::Member->value]);

    expect(Gate::forUser($owner)->allows('update', $tournament))->toBeTrue();
    expect(Gate::forUser($admin)->allows('inviteParticipant', $tournament))->toBeTrue();
    expect(Gate::forUser($member)->allows('update', $tournament))->toBeFalse();
    expect(Gate::forUser($outsider)->allows('view', $tournament))->toBeFalse();
});
