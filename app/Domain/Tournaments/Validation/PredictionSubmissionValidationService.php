<?php

namespace App\Domain\Tournaments\Validation;

use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Models\PredictionField;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PredictionSubmissionValidationService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function validate(PredictionField $field, array $payload): array
    {
        if (! $field->is_active) {
            throw ValidationException::withMessages([
                'prediction_field_id' => 'Predictions are disabled for this field.',
            ]);
        }

        $rules = [
            'value' => $this->rulesFor($field),
            'tournament_match_id' => $field->scope === PredictionScope::Match
                ? [
                    'required',
                    'integer',
                    Rule::exists('matches', 'id')->where('tournament_id', $field->tournament_id),
                ]
                : ['nullable'],
        ];

        $validated = Validator::make($payload, $rules)->validate();

        if ($field->scope === PredictionScope::Tournament && Arr::get($validated, 'tournament_match_id') !== null) {
            throw ValidationException::withMessages([
                'tournament_match_id' => 'Tournament scoped predictions cannot target a match.',
            ]);
        }

        return $validated;
    }

    /**
     * @return array<int, mixed>
     */
    protected function rulesFor(PredictionField $field): array
    {
        $schema = is_array($field->validation_schema) ? $field->validation_schema : [];

        $rules = match ($field->field_type) {
            PredictionFieldType::TeamPicker, PredictionFieldType::PlayerPicker => ['integer'],
            PredictionFieldType::Text => ['string'],
            PredictionFieldType::Number => ['numeric'],
            PredictionFieldType::Boolean => ['boolean'],
            PredictionFieldType::Date => ['date'],
            PredictionFieldType::Time => ['date_format:H:i'],
        };

        if (! Arr::get($schema, 'nullable', false)) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if (Arr::has($schema, 'min')) {
            $rules[] = 'min:'.Arr::get($schema, 'min');
        }

        if (Arr::has($schema, 'max')) {
            $rules[] = 'max:'.Arr::get($schema, 'max');
        }

        if (Arr::has($schema, 'regex')) {
            $rules[] = 'regex:'.Arr::get($schema, 'regex');
        }

        if (Arr::has($schema, 'in') && is_array(Arr::get($schema, 'in'))) {
            $rules[] = 'in:'.implode(',', Arr::get($schema, 'in'));
        }

        $extraRules = Arr::get($schema, 'rules', []);

        if (is_array($extraRules)) {
            foreach ($extraRules as $extraRule) {
                $rules[] = $extraRule;
            }
        }

        return $rules;
    }
}
