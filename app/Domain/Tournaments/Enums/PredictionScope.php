<?php

namespace App\Domain\Tournaments\Enums;

enum PredictionScope: string
{
    case Tournament = 'tournament';
    case Match = 'match';
}
