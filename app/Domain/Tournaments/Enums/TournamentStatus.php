<?php

namespace App\Domain\Tournaments\Enums;

enum TournamentStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Locked = 'locked';
    case Completed = 'completed';
    case Archived = 'archived';
}
