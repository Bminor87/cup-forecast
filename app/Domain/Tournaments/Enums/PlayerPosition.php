<?php

namespace App\Domain\Tournaments\Enums;

enum PlayerPosition: string
{
    case Goalkeeper = 'goalkeeper';
    case Defender = 'defender';
    case Midfielder = 'midfielder';
    case Forward = 'forward';
    case Center = 'center';
    case Wing = 'wing';
    case Unknown = 'unknown';
}
