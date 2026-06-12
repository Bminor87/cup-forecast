<?php

namespace App\Http\Controllers\Tournaments;

use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\Tournament;
use App\Http\Controllers\Controller;
use App\Jobs\RecalculateTournamentPredictionScoresJob;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PredictionScoreController extends Controller
{
    public function recalculate(Team $team): RedirectResponse
    {
        $tournament = Tournament::query()->findOrFail($team->id);

        Gate::authorize('create', [PredictionResult::class, $tournament]);

        RecalculateTournamentPredictionScoresJob::dispatch($tournament->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Score recalculation queued.')]);

        return back();
    }
}
