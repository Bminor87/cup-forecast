<?php

namespace App\Domain\Tournaments\Validation;

use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionOptionSource;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Models\Player;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentTeam;
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

        $this->validateOptionSourceMembership($field, $validated);

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function validateOptionSourceMembership(PredictionField $field, array $validated): void
    {
        if (! in_array($field->field_type, [PredictionFieldType::TeamPicker, PredictionFieldType::PlayerPicker], true)) {
            return;
        }

        $value = Arr::get($validated, 'value');
        if ($value === null) {
            return;
        }

        $source = $field->optionSource();
        if ($source === null) {
            return;
        }

        $match = null;
        if ($field->scope === PredictionScope::Match) {
            $matchId = (int) Arr::get($validated, 'tournament_match_id');
            $match = TournamentMatch::query()->find($matchId);

            if ($match === null) {
                throw ValidationException::withMessages([
                    'tournament_match_id' => 'Selected match context could not be found.',
                ]);
            }
        }

        $allowedValues = match ($source) {
            PredictionOptionSource::AllTournamentTeams => TournamentTeam::query()
                ->where('tournament_id', $field->tournament_id)
                ->pluck('id')
                ->map(fn (int $id): string => (string) $id)
                ->all(),
            PredictionOptionSource::MatchTeams => $match
                ? [
                    (string) $match->home_tournament_team_id,
                    (string) $match->away_tournament_team_id,
                ]
                : [],
            PredictionOptionSource::AllTournamentPlayers => Player::query()
                ->where('tournament_id', $field->tournament_id)
                ->pluck('id')
                ->map(fn (int $id): string => (string) $id)
                ->all(),
            PredictionOptionSource::MatchPlayers => $match
                ? Player::query()
                    ->where('tournament_id', $field->tournament_id)
                    ->whereIn('tournament_team_id', [$match->home_tournament_team_id, $match->away_tournament_team_id])
                    ->pluck('id')
                    ->map(fn (int $id): string => (string) $id)
                    ->all()
                : [],
            PredictionOptionSource::StaticOptions => collect($field->staticOptions())
                ->pluck('value')
                ->filter(fn (mixed $optionValue): bool => is_scalar($optionValue))
                ->map(fn (mixed $optionValue): string => (string) $optionValue)
                ->all(),
        };

        if (! in_array((string) $value, $allowedValues, true)) {
            throw ValidationException::withMessages([
                'value' => 'Selected option is not valid for this prediction field context.',
            ]);
        }
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
