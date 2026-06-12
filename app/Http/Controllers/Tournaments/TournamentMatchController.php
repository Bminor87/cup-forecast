<?php

namespace App\Http\Controllers\Tournaments;

use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StoreTournamentMatchRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TournamentMatchController extends Controller
{
    /**
     * Store a newly created tournament match.
     */
    public function store(StoreTournamentMatchRequest $request, Team $team): RedirectResponse
    {
        $tournament = Tournament::query()->findOrFail($team->id);

        Gate::authorize('create', [TournamentMatch::class, $tournament]);

        $tournament->matches()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Match created.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Remove the specified tournament match.
     */
    public function destroy(Team $team, TournamentMatch $tournamentMatch): RedirectResponse
    {
        abort_if($tournamentMatch->tournament_id !== $team->id, 404);

        Gate::authorize('delete', $tournamentMatch);

        $tournamentMatch->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Match deleted.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }
}
