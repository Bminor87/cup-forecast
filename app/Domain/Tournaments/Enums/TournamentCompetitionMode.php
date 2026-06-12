<?php

namespace App\Domain\Tournaments\Enums;

enum TournamentCompetitionMode: string
{
    case NationalTeams = 'national_teams';
    case ClubTeams = 'club_teams';
}
