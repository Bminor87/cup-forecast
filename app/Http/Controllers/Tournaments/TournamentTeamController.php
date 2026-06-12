<?php

namespace App\Http\Controllers\Tournaments;

use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreTournamentTeamRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TournamentTeamController extends Controller
{
    /**
     * Store a newly created tournament team.
     */
    public function store(StoreTournamentTeamRequest $request, Team $team): RedirectResponse
    {
        $tournament = Tournament::query()->findOrFail($team->id);

        Gate::authorize('create', [TournamentTeam::class, $tournament]);

        $tournament->tournamentTeams()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tournament team created.')]);

        return back();
    }

    /**
     * Remove the specified tournament team.
     */
    public function destroy(Team $team, TournamentTeam $tournamentTeam): RedirectResponse
    {
        abort_if($tournamentTeam->tournament_id !== $team->id, 404);

        Gate::authorize('delete', $tournamentTeam);

        $tournamentTeam->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tournament team deleted.')]);

        return back();
    }
}
