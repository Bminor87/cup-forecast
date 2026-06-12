<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateTeam;
use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Enums\TeamType;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\DeleteTeamRequest;
use App\Http\Requests\Teams\SaveTeamRequest;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    /**
     * Display a listing of the user's teams.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('teams/index', [
            'teams' => $user->toUserTeams(includeCurrent: true),
        ]);
    }

    /**
     * Store a newly created team.
     */
    public function store(SaveTeamRequest $request, CreateTeam $createTeam): RedirectResponse
    {
        $team = $createTeam->handle($request->user(), $request->validated('name'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team created.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Show the team edit page.
     */
    public function edit(Request $request, Team $team): Response
    {
        $user = $request->user();
        $tournament = Tournament::query()->findOrFail($team->id);

        return Inertia::render('teams/edit', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'isPersonal' => $team->is_personal,
            ],
            'members' => $team->members()->get()->map(function (User $member) {
                /** @var Membership $membership */
                $membership = $member->getRelation('pivot');

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatar' => $member->avatar ?? null,
                    'role' => $membership->role->value,
                    'role_label' => $membership->role->label(),
                ];
            }),
            'invitations' => $team->invitations()
                ->whereNull('accepted_at')
                ->get()
                ->map(fn ($invitation) => [
                    'code' => $invitation->code,
                    'email' => $invitation->email,
                    'role' => $invitation->role->value,
                    'role_label' => $invitation->role->label(),
                    'created_at' => $invitation->created_at->toISOString(),
                ]),
            'tournamentTeams' => $tournament->tournamentTeams()
                ->orderBy('name')
                ->get()
                ->map(fn (TournamentTeam $tournamentTeam) => [
                    'id' => $tournamentTeam->id,
                    'name' => $tournamentTeam->name,
                    'short_name' => $tournamentTeam->short_name,
                    'type' => $tournamentTeam->type->value,
                    'type_label' => ucfirst($tournamentTeam->type->value),
                ]),
            'matches' => $tournament->matches()
                ->with(['homeTournamentTeam', 'awayTournamentTeam'])
                ->orderBy('starts_at')
                ->get()
                ->map(fn (TournamentMatch $match) => [
                    'id' => $match->id,
                    'home_tournament_team_id' => $match->home_tournament_team_id,
                    'away_tournament_team_id' => $match->away_tournament_team_id,
                    'home_team_name' => $match->homeTournamentTeam->name,
                    'away_team_name' => $match->awayTournamentTeam->name,
                    'starts_at' => $match->starts_at->toISOString(),
                    'locks_at' => $match->locks_at?->toISOString(),
                    'status' => $match->status->value,
                    'status_label' => str_replace('_', ' ', ucfirst($match->status->value)),
                    'venue' => $match->venue,
                ]),
            'teamTypes' => collect(TeamType::cases())
                ->map(fn (TeamType $type) => ['value' => $type->value, 'label' => ucfirst($type->value)])
                ->values(),
            'matchStatuses' => collect(MatchStatus::cases())
                ->map(fn (MatchStatus $status) => [
                    'value' => $status->value,
                    'label' => str_replace('_', ' ', ucfirst($status->value)),
                ])
                ->values(),
            'canManageTournamentTeams' => Gate::forUser($user)->allows('create', [TournamentTeam::class, $tournament]),
            'canManageMatches' => Gate::forUser($user)->allows('create', [TournamentMatch::class, $tournament]),
            'permissions' => $user->toTeamPermissions($team),
            'availableRoles' => TeamRole::assignable(),
        ]);
    }

    /**
     * Update the specified team.
     */
    public function update(SaveTeamRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $team = DB::transaction(function () use ($request, $team) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();

            $team->update(['name' => $request->validated('name')]);

            return $team;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team updated.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Switch the user's current team.
     */
    public function switch(Request $request, Team $team): RedirectResponse
    {
        abort_unless($request->user()->belongsToTeam($team), 403);

        $request->user()->switchTeam($team);

        return back();
    }

    /**
     * Delete the specified team.
     */
    public function destroy(DeleteTeamRequest $request, Team $team): RedirectResponse
    {
        $user = $request->user();
        $fallbackTeam = $user->isCurrentTeam($team)
            ? $user->fallbackTeam($team)
            : null;

        DB::transaction(function () use ($user, $team) {
            User::where('current_team_id', $team->id)
                ->where('id', '!=', $user->id)
                ->each(fn (User $affectedUser) => $affectedUser->switchTeam($affectedUser->personalTeam()));

            $team->invitations()->delete();
            $team->memberships()->delete();
            $team->delete();
        });

        if ($fallbackTeam) {
            $user->switchTeam($fallbackTeam);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team deleted.')]);

        return to_route('teams.index');
    }
}
