<?php

namespace App\Domain\Tournaments\Enums;

enum TournamentSportType: string
{
    case Football = 'football';
    case IceHockey = 'ice_hockey';
    case Other = 'other';
}
