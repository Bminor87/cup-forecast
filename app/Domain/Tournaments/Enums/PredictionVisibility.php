<?php

namespace App\Domain\Tournaments\Enums;

enum PredictionVisibility: string
{
    case HiddenUntilLock = 'hidden_until_lock';
    case HiddenUntilResult = 'hidden_until_result';
    case AlwaysVisible = 'always_visible';

    public function shouldReveal(bool $isLocked, bool $isResolved): bool
    {
        return match ($this) {
            self::AlwaysVisible => true,
            self::HiddenUntilLock => $isLocked,
            self::HiddenUntilResult => $isResolved,
        };
    }
}
