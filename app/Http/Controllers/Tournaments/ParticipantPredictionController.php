<?php

namespace App\Http\Controllers\Tournaments;

use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Enums\PredictionStatus;
use App\Domain\Tournaments\Locking\PredictionLockService;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Validation\PredictionSubmissionValidationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\UpsertPredictionRequest;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantPredictionController extends Controller
{
    public function index(Request $request, string $current_team, PredictionLockService $lockService): Response
    {
        $team = Team::query()->where('slug', $current_team)->firstOrFail();
        $tournament = Tournament::query()->findOrFail($team->id);
        $user = $request->user();

        Gate::authorize('viewAny', [Prediction::class, $tournament]);

        $matches = $tournament->matches()
            ->with(['homeTournamentTeam:id,name', 'awayTournamentTeam:id,name'])
            ->orderBy('starts_at')
            ->get();

        $fields = $tournament->predictionFields()
            ->orderBy('scope')
            ->orderBy('id')
            ->get();

        $predictions = Prediction::query()
            ->where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy(fn (Prediction $prediction): string => $prediction->prediction_field_id.'|'.$prediction->context_key);

        $results = PredictionResult::query()
            ->where('tournament_id', $tournament->id)
            ->get()
            ->keyBy(fn (PredictionResult $result): string => $result->prediction_field_id.'|'.$result->context_key);

        $tournamentFields = $fields
            ->filter(fn (PredictionField $field): bool => $field->scope === PredictionScope::Tournament && $field->is_active)
            ->values()
            ->map(function (PredictionField $field) use ($predictions, $results, $lockService, $tournament): array {
                $contextKey = Prediction::tournamentContextKey();
                $prediction = $predictions->get($field->id.'|'.$contextKey);
                $result = $results->get($field->id.'|'.$contextKey);

                return $this->serializeFieldInstance(
                    field: $field,
                    tournament: $tournament,
                    lockService: $lockService,
                    contextKey: $contextKey,
                    prediction: $prediction,
                    result: $result,
                    match: null,
                );
            });

        $matchFields = $fields
            ->filter(fn (PredictionField $field): bool => $field->scope === PredictionScope::Match && $field->is_active)
            ->values();

        $matchPredictionGroups = $matches
            ->map(function (TournamentMatch $match) use ($matchFields, $predictions, $results, $lockService, $tournament): array {
                $contextKey = Prediction::contextKeyForMatch($match->id);

                $instances = $matchFields->map(function (PredictionField $field) use ($match, $contextKey, $predictions, $results, $lockService, $tournament): array {
                    $prediction = $predictions->get($field->id.'|'.$contextKey);
                    $result = $results->get($field->id.'|'.$contextKey);

                    return $this->serializeFieldInstance(
                        field: $field,
                        tournament: $tournament,
                        lockService: $lockService,
                        contextKey: $contextKey,
                        prediction: $prediction,
                        result: $result,
                        match: $match,
                    );
                });

                return [
                    'match' => [
                        'id' => $match->id,
                        'name' => $match->homeTournamentTeam->name.' vs '.$match->awayTournamentTeam->name,
                        'starts_at' => $match->starts_at->toISOString(),
                        'locks_at' => $match->locks_at?->toISOString(),
                    ],
                    'fields' => $instances->values(),
                ];
            })
            ->values();

        return Inertia::render('predictions/index', [
            'currentTeamSlug' => $team->slug,
            'tournamentPredictions' => $tournamentFields,
            'matchPredictions' => $matchPredictionGroups,
        ]);
    }

    public function upsert(
        UpsertPredictionRequest $request,
        string $current_team,
        int $predictionField,
        PredictionSubmissionValidationService $submissionValidator,
        PredictionLockService $lockService,
    ): JsonResponse {
        $team = Team::query()->where('slug', $current_team)->firstOrFail();
        $tournament = Tournament::query()->findOrFail($team->id);
        $predictionField = PredictionField::query()->findOrFail($predictionField);
        abort_if($predictionField->tournament_id !== $tournament->id, 404);

        Gate::authorize('create', [Prediction::class, $tournament]);

        try {
            $payload = $request->validated();

            $validated = $submissionValidator->validate($predictionField, [
                'value' => $payload['value'],
                'tournament_match_id' => $payload['tournament_match_id'] ?? null,
            ]);

            $match = isset($validated['tournament_match_id'])
                ? TournamentMatch::query()->find($validated['tournament_match_id'])
                : null;

            $contextKey = $predictionField->scope === PredictionScope::Match
                ? Prediction::contextKeyForMatch((int) $validated['tournament_match_id'])
                : Prediction::tournamentContextKey();

            /** @var Prediction $prediction */
            $prediction = Prediction::query()->firstOrNew([
                'tournament_id' => $tournament->id,
                'prediction_field_id' => $predictionField->id,
                'user_id' => $request->user()->id,
                'context_key' => $contextKey,
            ]);

            $prediction->setRelation('predictionField', $predictionField);
            $prediction->setRelation('tournament', $tournament);
            if ($match !== null) {
                $prediction->setRelation('tournamentMatch', $match);
            }

            if ($prediction->exists) {
                $lockService->syncPredictionLockState($prediction);
                $lockService->enforceUnlocked($prediction);
            } elseif ($lockService->shouldBeLocked($prediction)) {
                throw ValidationException::withMessages([
                    'prediction' => 'Predictions are locked for this field.',
                ]);
            }

            $prediction->fill([
                'tournament_match_id' => $validated['tournament_match_id'] ?? null,
                'value' => ['value' => $validated['value']],
                'status' => $payload['status'],
                'submitted_at' => now(),
            ]);
            $prediction->save();

            $lockService->syncPredictionLockState($prediction);
            $prediction->refresh();
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $exception->errors(),
                ], 422);
            }

            throw $exception;
        }

        return response()->json([
            'id' => $prediction->id,
            'status' => $prediction->status->value,
            'locked' => $prediction->isLocked(),
            'last_saved_at' => $prediction->updated_at?->toISOString(),
            'value' => $prediction->value['value'] ?? null,
        ]);
    }

    private function serializeFieldInstance(
        PredictionField $field,
        Tournament $tournament,
        PredictionLockService $lockService,
        string $contextKey,
        ?Prediction $prediction,
        ?PredictionResult $result,
        ?TournamentMatch $match,
    ): array {
        $probe = $prediction ?? $this->makeProbePrediction($field, $tournament, $contextKey, $match);

        $isLocked = $prediction?->isLocked() ?? false;
        if (! $isLocked) {
            $isLocked = $lockService->shouldBeLocked($probe);
        }

        $isResolved = $result?->status === PredictionResultStatus::Resolved;
        $isResultVisible = $field->visibility->shouldReveal($isLocked, $isResolved);

        return [
            'id' => $field->id,
            'scope' => $field->scope->value,
            'field_type' => $field->field_type->value,
            'label' => $field->label,
            'description' => $field->description,
            'visibility' => $field->visibility->value,
            'option_source' => $field->optionSource()?->value,
            'options' => $this->optionsForField($field, $match),
            'validation_schema' => $field->validation_schema,
            'context_key' => $contextKey,
            'tournament_match_id' => $match?->id,
            'value' => $prediction->value['value'] ?? null,
            'status' => $prediction?->status->value ?? ($isLocked ? PredictionStatus::Locked->value : PredictionStatus::Draft->value),
            'is_locked' => $isLocked,
            'last_saved_at' => $prediction?->updated_at?->toISOString(),
            'result_status' => $result?->status->value,
            'result_value' => $isResultVisible ? ($result->value['value'] ?? null) : null,
            'result_is_visible' => $isResultVisible,
        ];
    }

    private function makeProbePrediction(
        PredictionField $field,
        Tournament $tournament,
        string $contextKey,
        ?TournamentMatch $match,
    ): Prediction {
        $prediction = new Prediction([
            'tournament_id' => $field->tournament_id,
            'prediction_field_id' => $field->id,
            'tournament_match_id' => $match?->id,
            'context_key' => $contextKey,
            'status' => PredictionStatus::Submitted,
            'value' => ['value' => null],
        ]);

        $prediction->setRelation('predictionField', $field);
        $prediction->setRelation('tournament', $tournament);
        if ($match !== null) {
            $prediction->setRelation('tournamentMatch', $match);
        }

        return $prediction;
    }

    /**
     * @return array<int, array{value: mixed, label: string}>
     */
    private function optionsForField(PredictionField $field, ?TournamentMatch $match): array
    {
        $source = $field->optionSource();

        if ($source === null) {
            return [];
        }

        return match ($source->value) {
            'all_tournament_teams' => $field->tournament
                ->tournamentTeams()
                ->orderBy('name')
                ->get()
                ->map(fn ($team): array => [
                    'value' => $team->id,
                    'label' => $team->name,
                ])
                ->values()
                ->all(),
            'match_teams' => $match
                ? collect([$match->homeTournamentTeam, $match->awayTournamentTeam])
                    ->filter()
                    ->map(fn ($team): array => [
                        'value' => $team->id,
                        'label' => $team->name,
                    ])
                    ->values()
                    ->all()
                : [],
            'all_tournament_players' => $field->tournament
                ->players()
                ->with('tournamentTeam:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn ($player): array => [
                    'value' => $player->id,
                    'label' => $player->name.($player->tournamentTeam?->name ? ' ('.$player->tournamentTeam->name.')' : ''),
                ])
                ->values()
                ->all(),
            'match_players' => $match
                ? $field->tournament
                    ->players()
                    ->whereIn('tournament_team_id', [$match->home_tournament_team_id, $match->away_tournament_team_id])
                    ->with('tournamentTeam:id,name')
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($player): array => [
                        'value' => $player->id,
                        'label' => $player->name.($player->tournamentTeam?->name ? ' ('.$player->tournamentTeam->name.')' : ''),
                    ])
                    ->values()
                    ->all()
                : [],
            'static_options' => $field->staticOptions(),
            default => [],
        };
    }
}
