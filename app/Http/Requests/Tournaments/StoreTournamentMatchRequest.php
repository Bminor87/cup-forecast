<?php

namespace App\Http\Requests\Tournaments;

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTournamentMatchRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'home_tournament_team_id' => [
                'required',
                'integer',
                Rule::exists('tournament_teams', 'id')->where('tournament_id', $team->id),
                'different:away_tournament_team_id',
            ],
            'away_tournament_team_id' => [
                'required',
                'integer',
                Rule::exists('tournament_teams', 'id')->where('tournament_id', $team->id),
            ],
            'starts_at' => ['required', 'date'],
            'locks_at' => ['nullable', 'date', 'before_or_equal:starts_at'],
            'status' => ['required', 'string', Rule::in(array_column(MatchStatus::cases(), 'value'))],
            'venue' => ['nullable', 'string', 'max:255'],
        ];
    }
}
