<?php

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Enums\TournamentCompetitionMode;
use App\Domain\Tournaments\Enums\TournamentSportType;
use App\Domain\Tournaments\Enums\TournamentStatus;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Enums\TeamRole;
use App\Models\User;
use Database\Seeders\TournamentMatchSeeder;
use Database\Seeders\TournamentTeamSeeder;
use Illuminate\Support\Facades\Gate;

test('tournament has matches and teams have home and away matches', function () {
    $tournament = Tournament::query()->create([
        'name' => 'Euro 2032',
        'sport_type' => TournamentSportType::Football,
        'competition_mode' => TournamentCompetitionMode::NationalTeams,
        'status' => TournamentStatus::Active,
    ]);

    $homeTeam = TournamentTeam::factory()->forTournament($tournament)->create();
    $awayTeam = TournamentTeam::factory()->forTournament($tournament)->create();

    $match = TournamentMatch::factory()->forTeams($tournament, $homeTeam, $awayTeam)->create();

    expect($tournament->matches()->count())->toBe(1);
    expect($homeTeam->homeMatches()->count())->toBe(1);
    expect($awayTeam->awayMatches()->count())->toBe(1);
    expect($match->homeTournamentTeam->is($homeTeam))->toBeTrue();
    expect($match->awayTournamentTeam->is($awayTeam))->toBeTrue();
});

test('match policy allows admin and owner but blocks member', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();

    $tournament = Tournament::query()->create([
        'name' => 'Hockey Worlds 2031',
        'sport_type' => TournamentSportType::IceHockey,
        'competition_mode' => TournamentCompetitionMode::NationalTeams,
        'status' => TournamentStatus::Active,
    ]);

    $tournament->participants()->attach($owner, ['role' => TeamRole::Owner->value]);
    $tournament->participants()->attach($admin, ['role' => TeamRole::Admin->value]);
    $tournament->participants()->attach($member, ['role' => TeamRole::Member->value]);

    $homeTeam = TournamentTeam::factory()->forTournament($tournament)->create();
    $awayTeam = TournamentTeam::factory()->forTournament($tournament)->create();

    $match = TournamentMatch::factory()->forTeams($tournament, $homeTeam, $awayTeam)->create();

    expect(Gate::forUser($owner)->allows('create', [TournamentMatch::class, $tournament]))->toBeTrue();
    expect(Gate::forUser($admin)->allows('update', $match))->toBeTrue();
    expect(Gate::forUser($member)->allows('delete', $match))->toBeFalse();
});

test('match cannot have same home and away team', function () {
    $tournament = Tournament::query()->create([
        'name' => 'World Cup 2034',
        'sport_type' => TournamentSportType::Football,
        'competition_mode' => TournamentCompetitionMode::NationalTeams,
        'status' => TournamentStatus::Active,
    ]);

    $sameTeam = TournamentTeam::factory()->forTournament($tournament)->create();

    expect(fn () => TournamentMatch::query()->create([
        'tournament_id' => $tournament->id,
        'home_tournament_team_id' => $sameTeam->id,
        'away_tournament_team_id' => $sameTeam->id,
        'starts_at' => now()->addDay(),
        'status' => MatchStatus::Scheduled,
    ]))->toThrow(InvalidArgumentException::class, 'Home and away teams must be different.');
});

test('match teams must belong to same tournament as match', function () {
    $tournamentA = Tournament::query()->create([
        'name' => 'Tournament A',
        'sport_type' => TournamentSportType::Football,
        'competition_mode' => TournamentCompetitionMode::NationalTeams,
        'status' => TournamentStatus::Active,
    ]);

    $tournamentB = Tournament::query()->create([
        'name' => 'Tournament B',
        'sport_type' => TournamentSportType::Football,
        'competition_mode' => TournamentCompetitionMode::NationalTeams,
        'status' => TournamentStatus::Active,
    ]);

    $homeTeam = TournamentTeam::factory()->forTournament($tournamentA)->create();
    $awayTeam = TournamentTeam::factory()->forTournament($tournamentB)->create();

    expect(fn () => TournamentMatch::query()->create([
        'tournament_id' => $tournamentA->id,
        'home_tournament_team_id' => $homeTeam->id,
        'away_tournament_team_id' => $awayTeam->id,
        'starts_at' => now()->addDay(),
        'status' => MatchStatus::Scheduled,
    ]))->toThrow(InvalidArgumentException::class, 'Home and away teams must belong to the same tournament as the match.');
});

test('match seeder creates sample matches once', function () {
    $this->seed(TournamentTeamSeeder::class);
    $this->seed(TournamentMatchSeeder::class);
    $this->seed(TournamentMatchSeeder::class);

    $tournament = Tournament::query()->where('slug', 'sample-football-world-cup')->firstOrFail();

    expect($tournament->matches()->count())->toBe(4);
});
