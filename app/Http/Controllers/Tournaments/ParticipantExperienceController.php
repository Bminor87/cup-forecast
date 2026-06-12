<?php

namespace App\Http\Controllers\Tournaments;

use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Enums\PredictionStatus;
use App\Domain\Tournaments\Locking\PredictionLockService;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\PredictionScore;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantExperienceController extends Controller
{
    public function tournament(Request $request, string $current_team, PredictionLockService $lockService): Response
    {
        [$team, $tournament] = $this->teamContext($current_team);

        Gate::authorize('viewAny', [Prediction::class, $tournament]);

        [$tournamentFields] = $this->predictionPayload($request, $tournament, $lockService);

        return Inertia::render('predictions/tournament', [
            'currentTeamSlug' => $team->slug,
            'tournamentPredictions' => $tournamentFields,
        ]);
    }

    public function matches(Request $request, string $current_team, PredictionLockService $lockService): Response
    {
        [$team, $tournament] = $this->teamContext($current_team);

        Gate::authorize('viewAny', [Prediction::class, $tournament]);

        [, $matchPredictionGroups] = $this->predictionPayload($request, $tournament, $lockService);

        return Inertia::render('predictions/matches', [
            'currentTeamSlug' => $team->slug,
            'matchPredictions' => $matchPredictionGroups,
        ]);
    }

    public function leaderboard(Request $request, string $current_team): Response
    {
        [$team, $tournament] = $this->teamContext($current_team);

        Gate::authorize('viewAny', [Prediction::class, $tournament]);

        $scoreTotals = PredictionScore::query()
            ->select('user_id')
            ->selectRaw('SUM(points) as points')
            ->where('tournament_id', $tournament->id)
            ->groupBy('user_id');

        $rows = $tournament->participants()
            ->select('users.id', 'users.name')
            ->leftJoinSub($scoreTotals, 'score_totals', fn ($join) => $join->on('users.id', '=', 'score_totals.user_id'))
            ->orderByRaw('COALESCE(score_totals.points, 0) DESC')
            ->orderBy('users.name')
            ->get()
            ->values();

        $leaderboard = $rows
            ->map(fn ($row, int $index): array => [
                'rank' => $index + 1,
                'participant' => $row->name,
                'points' => (int) ($row->points ?? 0),
            ]);

        return Inertia::render('predictions/leaderboard', [
            'currentTeamSlug' => $team->slug,
            'leaderboard' => $leaderboard,
        ]);
    }

    public function rules(Request $request, string $current_team): Response
    {
        [$team, $tournament] = $this->teamContext($current_team);

        Gate::authorize('viewAny', [Prediction::class, $tournament]);

        $tournamentRuleFields = $tournament->predictionFields()
            ->where('scope', PredictionScope::Tournament->value)
            ->where('is_active', true)
            ->orderBy('created_at')
            ->get(['label']);

        $matchRuleFields = $tournament->predictionFields()
            ->where('scope', PredictionScope::Match->value)
            ->where('is_active', true)
            ->orderBy('created_at')
            ->get(['label']);

        return Inertia::render('predictions/rules', [
            'currentTeamSlug' => $team->slug,
            'rules' => [
                'tournament_fields' => $tournamentRuleFields->pluck('label')->values(),
                'match_fields' => $matchRuleFields->pluck('label')->values(),
                'lock_rule' => 'Predictions lock automatically at tournament start for tournament picks and at match lock time for match picks.',
                'scoring_rule' => 'Points come from persisted official score calculations after results are resolved.',
            ],
        ]);
    }

    /**
     * @return array{0: Team, 1: Tournament}
     */
    protected function teamContext(string $teamSlug): array
    {
        $team = Team::query()->where('slug', $teamSlug)->firstOrFail();
        $tournament = Tournament::query()->findOrFail($team->id);

        return [$team, $tournament];
    }

    /**
     * @return array{0: Collection<int, array<string, mixed>>, 1: Collection<int, array<string, mixed>>}
     */
    protected function predictionPayload(Request $request, Tournament $tournament, PredictionLockService $lockService): array
    {
        $user = $request->user();

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
                        'home_team_name' => $match->homeTournamentTeam->name,
                        'away_team_name' => $match->awayTournamentTeam->name,
                        'starts_at' => $match->starts_at->toISOString(),
                        'locks_at' => $match->locks_at?->toISOString(),
                    ],
                    'fields' => $instances->values(),
                ];
            })
            ->values();

        return [$tournamentFields, $matchPredictionGroups];
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
                    'label' => $player->name.($player->tournamentTeam ? ' ('.$player->tournamentTeam->name.')' : ''),
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
                        'label' => $player->name.($player->tournamentTeam ? ' ('.$player->tournamentTeam->name.')' : ''),
                    ])
                    ->values()
                    ->all()
                : [],
            'static_options' => $field->staticOptions(),
            default => [],
        };
    }
}
