<?php

namespace App\Domain\Tournaments\Enums;

enum PredictionOptionSource: string
{
    case AllTournamentTeams = 'all_tournament_teams';
    case MatchTeams = 'match_teams';
    case AllTournamentPlayers = 'all_tournament_players';
    case MatchPlayers = 'match_players';
    case StaticOptions = 'static_options';
}
