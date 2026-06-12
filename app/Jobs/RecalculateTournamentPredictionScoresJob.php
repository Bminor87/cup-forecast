<?php

namespace App\Jobs;

use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Scoring\PredictionScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateTournamentPredictionScoresJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $tournamentId,
    ) {}

    public function handle(PredictionScoringService $scoringService): void
    {
        $tournament = Tournament::query()->find($this->tournamentId);

        if ($tournament === null) {
            return;
        }

        $scoringService->recalculateTournament($tournament);
    }
}
