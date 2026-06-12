<?php

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Enums\TeamType;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('owner can create tournament teams from settings workflow', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'World Cup 2026']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $this->actingAs($owner)
        ->post(route('teams.tournament-teams.store', $team), [
            'name' => 'Finland',
            'short_name' => 'FIN',
            'type' => TeamType::National->value,
        ])
        ->assertRedirect(route('teams.edit', $team));

    $this->assertDatabaseHas('tournament_teams', [
        'tournament_id' => $team->id,
        'name' => 'Finland',
        'short_name' => 'FIN',
        'type' => TeamType::National->value,
    ]);
});

test('owner can create matches between tournament teams from settings workflow', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'World Cup 2026']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $tournament = Tournament::query()->findOrFail($team->id);

    $finland = TournamentTeam::factory()->forTournament($tournament)->create(['name' => 'Finland']);
    $sweden = TournamentTeam::factory()->forTournament($tournament)->create(['name' => 'Sweden']);

    $this->actingAs($owner)
        ->post(route('teams.matches.store', $team), [
            'home_tournament_team_id' => $finland->id,
            'away_tournament_team_id' => $sweden->id,
            'starts_at' => now()->addDay()->toISOString(),
            'status' => MatchStatus::Scheduled->value,
            'venue' => 'Olympiastadion',
        ])
        ->assertRedirect(route('teams.edit', $team));

    $this->assertDatabaseHas('matches', [
        'tournament_id' => $team->id,
        'home_tournament_team_id' => $finland->id,
        'away_tournament_team_id' => $sweden->id,
        'status' => MatchStatus::Scheduled->value,
        'venue' => 'Olympiastadion',
    ]);
});

test('settings edit page contains tournament teams and matches data for workflow', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'World Cup 2026']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $tournament = Tournament::query()->findOrFail($team->id);

    $finland = TournamentTeam::factory()->forTournament($tournament)->create(['name' => 'Finland']);
    $sweden = TournamentTeam::factory()->forTournament($tournament)->create(['name' => 'Sweden']);

    TournamentMatch::factory()->forTeams($tournament, $finland, $sweden)->create();

    $this->actingAs($owner)
        ->get(route('teams.edit', $team))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teams/edit')
            ->has('tournamentTeams', 2)
            ->has('matches', 1)
            ->where('canManageTournamentTeams', true)
            ->where('canManageMatches', true),
        );
});

test('owner can set up requested world cup 2026 workflow', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'World Cup 2026']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $teamNames = ['Finland', 'Sweden', 'Brazil', 'Argentina'];

    foreach ($teamNames as $name) {
        $this->actingAs($owner)
            ->post(route('teams.tournament-teams.store', $team), [
                'name' => $name,
                'type' => TeamType::National->value,
            ])
            ->assertRedirect(route('teams.edit', $team));
    }

    $finland = TournamentTeam::query()->where('tournament_id', $team->id)->where('name', 'Finland')->firstOrFail();
    $sweden = TournamentTeam::query()->where('tournament_id', $team->id)->where('name', 'Sweden')->firstOrFail();
    $brazil = TournamentTeam::query()->where('tournament_id', $team->id)->where('name', 'Brazil')->firstOrFail();
    $argentina = TournamentTeam::query()->where('tournament_id', $team->id)->where('name', 'Argentina')->firstOrFail();

    $this->actingAs($owner)
        ->post(route('teams.matches.store', $team), [
            'home_tournament_team_id' => $finland->id,
            'away_tournament_team_id' => $sweden->id,
            'starts_at' => now()->addDay()->toISOString(),
            'status' => MatchStatus::Scheduled->value,
        ])
        ->assertRedirect(route('teams.edit', $team));

    $this->actingAs($owner)
        ->post(route('teams.matches.store', $team), [
            'home_tournament_team_id' => $brazil->id,
            'away_tournament_team_id' => $argentina->id,
            'starts_at' => now()->addDays(2)->toISOString(),
            'status' => MatchStatus::Scheduled->value,
        ])
        ->assertRedirect(route('teams.edit', $team));

    $this->assertDatabaseHas('matches', [
        'tournament_id' => $team->id,
        'home_tournament_team_id' => $finland->id,
        'away_tournament_team_id' => $sweden->id,
    ]);

    $this->assertDatabaseHas('matches', [
        'tournament_id' => $team->id,
        'home_tournament_team_id' => $brazil->id,
        'away_tournament_team_id' => $argentina->id,
    ]);
});
