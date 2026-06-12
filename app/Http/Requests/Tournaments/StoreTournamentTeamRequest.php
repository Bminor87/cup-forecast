<?php

namespace App\Http\Requests\Tournaments;

use App\Domain\Tournaments\Enums\TeamType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTournamentTeamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:16'],
            'type' => ['required', 'string', Rule::in(array_column(TeamType::cases(), 'value'))],
        ];
    }
}
