<?php

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Enums\TeamType;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

beforeEach(function () {
    $this->withoutMiddleware([
        ValidateCsrfToken::class,
        VerifyCsrfToken::class,
        ValidateSessionWithWorkOS::class,
    ]);
});

test('owner can create tournament teams from settings workflow', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'World Cup 2026']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $this->actingAs($owner)
        ->from(route('dashboard', ['current_team' => $team->slug]))
        ->post(route('teams.tournament-teams.store', $team), [
            'name' => 'Finland',
            'short_name' => 'FIN',
            'type' => TeamType::National->value,
        ])
        ->assertRedirect(route('dashboard', ['current_team' => $team->slug]));

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
        ->from(route('dashboard', ['current_team' => $team->slug]))
        ->post(route('teams.matches.store', $team), [
            'home_tournament_team_id' => $finland->id,
            'away_tournament_team_id' => $sweden->id,
            'starts_at' => now()->addDay()->toISOString(),
            'status' => MatchStatus::Scheduled->value,
            'venue' => 'Olympiastadion',
        ])
        ->assertRedirect(route('dashboard', ['current_team' => $team->slug]));

    $this->assertDatabaseHas('matches', [
        'tournament_id' => $team->id,
        'home_tournament_team_id' => $finland->id,
        'away_tournament_team_id' => $sweden->id,
        'status' => MatchStatus::Scheduled->value,
        'venue' => 'Olympiastadion',
    ]);
});

test('dashboard contains tournament teams and matches data for workflow', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'World Cup 2026']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $tournament = Tournament::query()->findOrFail($team->id);

    $finland = TournamentTeam::factory()->forTournament($tournament)->create(['name' => 'Finland']);
    $sweden = TournamentTeam::factory()->forTournament($tournament)->create(['name' => 'Sweden']);

    TournamentMatch::factory()->forTeams($tournament, $finland, $sweden)->create();

    $this->actingAs($owner)
        ->get(route('dashboard', ['current_team' => $team->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
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
            ->from(route('dashboard', ['current_team' => $team->slug]))
            ->post(route('teams.tournament-teams.store', $team), [
                'name' => $name,
                'type' => TeamType::National->value,
            ])
            ->assertRedirect(route('dashboard', ['current_team' => $team->slug]));
    }

    $finland = TournamentTeam::query()->where('tournament_id', $team->id)->where('name', 'Finland')->firstOrFail();
    $sweden = TournamentTeam::query()->where('tournament_id', $team->id)->where('name', 'Sweden')->firstOrFail();
    $brazil = TournamentTeam::query()->where('tournament_id', $team->id)->where('name', 'Brazil')->firstOrFail();
    $argentina = TournamentTeam::query()->where('tournament_id', $team->id)->where('name', 'Argentina')->firstOrFail();

    $this->actingAs($owner)
        ->from(route('dashboard', ['current_team' => $team->slug]))
        ->post(route('teams.matches.store', $team), [
            'home_tournament_team_id' => $finland->id,
            'away_tournament_team_id' => $sweden->id,
            'starts_at' => now()->addDay()->toISOString(),
            'status' => MatchStatus::Scheduled->value,
        ])
        ->assertRedirect(route('dashboard', ['current_team' => $team->slug]));

    $this->actingAs($owner)
        ->from(route('dashboard', ['current_team' => $team->slug]))
        ->post(route('teams.matches.store', $team), [
            'home_tournament_team_id' => $brazil->id,
            'away_tournament_team_id' => $argentina->id,
            'starts_at' => now()->addDays(2)->toISOString(),
            'status' => MatchStatus::Scheduled->value,
        ])
        ->assertRedirect(route('dashboard', ['current_team' => $team->slug]));

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
