<?php

namespace App\Http\Controllers\Tournaments;

use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PredictionResultPageController extends Controller
{
    public function tournament(Request $request, Team $team): Response
    {
        $tournament = Tournament::query()->findOrFail($team->id);

        Gate::authorize('viewAny', [PredictionResult::class, $tournament]);

        $fields = $tournament->predictionFields()
            ->where('scope', PredictionScope::Tournament->value)
            ->with(['predictionResults' => fn ($query) => $query->where('context_key', 'tournament')])
            ->orderBy('created_at')
            ->get()
            ->map(fn (PredictionField $field): array => [
                'id' => $field->id,
                'label' => $field->label,
                'field_type' => $field->field_type->value,
                'result' => $field->predictionResults
                    ->sortByDesc('updated_at')
                    ->values()
                    ->map(fn (PredictionResult $result): array => [
                        'id' => $result->id,
                        'status' => $result->status->value,
                        'value' => $result->value,
                        'resolved_at' => $result->resolved_at?->toISOString(),
                    ])
                    ->first(),
            ])
            ->values();

        return Inertia::render('teams/prediction-results/tournament', [
            'teamSlug' => $team->slug,
            'fields' => $fields,
            'resultStatuses' => $this->resultStatuses(),
            'canManageResults' => Gate::forUser($request->user())->allows('create', [PredictionResult::class, $tournament]),
        ]);
    }

    public function matches(Request $request, Team $team): Response
    {
        $tournament = Tournament::query()->findOrFail($team->id);

        Gate::authorize('viewAny', [PredictionResult::class, $tournament]);

        $fields = $tournament->predictionFields()
            ->where('scope', PredictionScope::Match->value)
            ->orderBy('created_at')
            ->get();

        $resultsByContext = PredictionResult::query()
            ->where('tournament_id', $tournament->id)
            ->whereIn('prediction_field_id', $fields->pluck('id'))
            ->get()
            ->keyBy(fn (PredictionResult $result): string => $result->prediction_field_id.'|'.$result->context_key);

        $matches = $tournament->matches()
            ->with(['homeTournamentTeam:id,name', 'awayTournamentTeam:id,name'])
            ->orderBy('starts_at')
            ->get()
            ->map(function (TournamentMatch $match) use ($fields, $resultsByContext): array {
                $contextKey = 'match:'.$match->id;

                return [
                    'id' => $match->id,
                    'name' => $match->homeTournamentTeam->name.' vs '.$match->awayTournamentTeam->name,
                    'starts_at' => $match->starts_at->toISOString(),
                    'fields' => $fields->map(function (PredictionField $field) use ($resultsByContext, $contextKey): array {
                        $result = $resultsByContext->get($field->id.'|'.$contextKey);

                        return [
                            'id' => $field->id,
                            'label' => $field->label,
                            'field_type' => $field->field_type->value,
                            'result' => $result ? [
                                'id' => $result->id,
                                'status' => $result->status->value,
                                'value' => $result->value,
                                'resolved_at' => $result->resolved_at?->toISOString(),
                            ] : null,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return Inertia::render('teams/prediction-results/matches', [
            'teamSlug' => $team->slug,
            'matches' => $matches,
            'resultStatuses' => $this->resultStatuses(),
            'canManageResults' => Gate::forUser($request->user())->allows('create', [PredictionResult::class, $tournament]),
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function resultStatuses(): array
    {
        return collect(PredictionResultStatus::cases())
            ->map(fn (PredictionResultStatus $status): array => [
                'value' => $status->value,
                'label' => ucfirst($status->value),
            ])
            ->values()
            ->all();
    }
}
