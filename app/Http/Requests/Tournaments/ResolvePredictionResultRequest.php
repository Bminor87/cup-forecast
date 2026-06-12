<?php

namespace App\Http\Requests\Tournaments;

use App\Domain\Tournaments\Enums\PredictionResultStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolvePredictionResultRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $resultValue = $this->input('result_value');
        $decodedValue = is_string($resultValue) && $resultValue !== ''
            ? json_decode($resultValue, true)
            : $this->input('value');

        $normalizedValue = is_array($decodedValue) && array_key_exists('value', $decodedValue)
            ? $decodedValue['value']
            : $decodedValue;

        $this->merge([
            'value' => $normalizedValue,
            'tournament_match_id' => $this->input('tournament_match_id') === '' ? null : $this->input('tournament_match_id'),
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
            'value' => ['required'],
            'tournament_match_id' => ['nullable', 'integer'],
            'status' => ['required', 'string', Rule::in(array_column(PredictionResultStatus::cases(), 'value'))],
        ];
    }
}
