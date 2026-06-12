<?php

namespace App\Http\Requests\Tournaments;

use App\Domain\Tournaments\Enums\PredictionStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPredictionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tournament_match_id' => $this->input('tournament_match_id') === '' ? null : $this->input('tournament_match_id'),
            'status' => $this->input('status', PredictionStatus::Submitted->value),
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'value' => ['present'],
            'tournament_match_id' => ['nullable', 'integer'],
            'status' => [
                'required',
                'string',
                Rule::in([
                    PredictionStatus::Draft->value,
                    PredictionStatus::Submitted->value,
                ]),
            ],
        ];
    }
}
