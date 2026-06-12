<?php

namespace App\Http\Requests\Tournaments;

use App\Domain\Tournaments\Enums\PlayerPosition;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlayerRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'tournament_team_id' => [
                'required',
                'integer',
                Rule::exists('tournament_teams', 'id')->where('tournament_id', $team->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:120'],
            'shirt_number' => ['nullable', 'integer', 'min:1', 'max:99'],
            'position' => ['nullable', 'string', Rule::in(array_column(PlayerPosition::cases(), 'value'))],
        ];
    }
}
