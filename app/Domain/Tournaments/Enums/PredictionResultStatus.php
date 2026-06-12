<?php

namespace App\Domain\Tournaments\Enums;

enum PredictionResultStatus: string
{
    case Pending = 'pending';
    case Resolved = 'resolved';
}
