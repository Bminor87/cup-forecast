<?php

namespace App\Domain\Tournaments\Enums;

enum PredictionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Locked = 'locked';
}
