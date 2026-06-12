<?php

namespace App\Domain\Tournaments\Locking;

use App\Domain\Tournaments\Enums\PredictionStatus;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\TournamentMatch;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class PredictionLockService
{
    public function shouldBeLocked(Prediction $prediction, ?CarbonInterface $at = null): bool
    {
        $at ??= now();

        $field = $prediction->predictionField;

        if ($field->isManuallyLocked()) {
            return true;
        }

        if ($prediction->tournamentMatch === null) {
            return false;
        }

        $lockTime = $prediction->tournamentMatch->locks_at ?? $prediction->tournamentMatch->starts_at;

        return $lockTime !== null && $lockTime->lessThanOrEqualTo($at);
    }

    public function enforceUnlocked(Prediction $prediction): void
    {
        if ($prediction->isLocked()) {
            throw ValidationException::withMessages([
                'prediction' => 'Locked predictions cannot be modified.',
            ]);
        }
    }

    public function lockPrediction(Prediction $prediction): void
    {
        if ($prediction->isLocked()) {
            return;
        }

        $prediction->status = PredictionStatus::Locked;
        $prediction->locked_at = now();
        $prediction->save();
    }

    public function syncPredictionLockState(Prediction $prediction): bool
    {
        if ($this->shouldBeLocked($prediction)) {
            $this->lockPrediction($prediction);

            return true;
        }

        return false;
    }

    public function syncFieldPredictions(PredictionField $field, ?TournamentMatch $match = null): int
    {
        $lockedCount = 0;

        $predictions = $field->predictions()->whereNull('locked_at');

        if ($match !== null) {
            $predictions->where('tournament_match_id', $match->id);
        }

        $predictions->cursor()
            ->each(function (Prediction $prediction) use (&$lockedCount): void {
                if ($this->syncPredictionLockState($prediction)) {
                    $lockedCount++;
                }
            });

        return $lockedCount;
    }
}
