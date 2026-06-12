<?php

namespace App\Http\Requests\Tournaments;

use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Enums\PredictionVisibility;
use App\Domain\Tournaments\Models\PredictionField;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePredictionFieldRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $validationSchema = $this->input('validation_schema');
        $configuration = $this->input('configuration');

        $this->merge([
            'validation_schema' => is_string($validationSchema) && $validationSchema !== ''
                ? json_decode($validationSchema, true)
                : $validationSchema,
            'configuration' => is_string($configuration) && $configuration !== ''
                ? json_decode($configuration, true)
                : $configuration,
            'is_active' => filter_var($this->input('is_active', true), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false,
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
        $team = $this->route('team');
        $predictionField = $this->route('predictionField');

        $teamId = $team instanceof Team
            ? $team->id
            : Team::query()->where('slug', (string) $team)->value('id');

        $predictionFieldId = $predictionField instanceof PredictionField
            ? $predictionField->id
            : (int) $predictionField;

        return [
            'scope' => ['required', 'string', Rule::in(array_column(PredictionScope::cases(), 'value'))],
            'field_type' => ['required', 'string', Rule::in(array_column(PredictionFieldType::cases(), 'value'))],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'key' => [
                'required',
                'string',
                'max:100',
                Rule::unique('prediction_fields', 'key')
                    ->where('tournament_id', $teamId)
                    ->ignore($predictionFieldId),
            ],
            'visibility' => ['required', 'string', Rule::in(array_column(PredictionVisibility::cases(), 'value'))],
            'validation_schema' => ['nullable', 'array'],
            'scoring_strategy_key' => ['required', 'string', 'max:64'],
            'configuration' => ['nullable', 'array'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
