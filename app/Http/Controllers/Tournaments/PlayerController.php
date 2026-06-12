<?php

namespace App\Http\Controllers\Tournaments;

use App\Domain\Tournaments\Models\Player;
use App\Domain\Tournaments\Models\Tournament;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\StorePlayerRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PlayerController extends Controller
{
    public function store(StorePlayerRequest $request, Team $team): RedirectResponse
    {
        $tournament = Tournament::query()->findOrFail($team->id);

        Gate::authorize('create', [Player::class, $tournament]);

        $tournament->players()->create([
            ...$request->validated(),
            'tournament_id' => $tournament->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Player created.')]);

        return back();
    }

    public function destroy(Team $team, Player $player): RedirectResponse
    {
        abort_if($player->tournament_id !== $team->id, 404);

        Gate::authorize('delete', $player);

        $player->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Player deleted.')]);

        return back();
    }
}
