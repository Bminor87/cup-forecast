<?php

namespace App\Domain\Tournaments\Enums;

enum PredictionFieldType: string
{
    case TeamPicker = 'team_picker';
    case PlayerPicker = 'player_picker';
    case Text = 'text';
    case Number = 'number';
    case Boolean = 'boolean';
    case Date = 'date';
    case Time = 'time';
}
