<?php

namespace App\Http\Controllers;

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Enums\PredictionFieldType;
use App\Domain\Tournaments\Enums\PredictionResultStatus;
use App\Domain\Tournaments\Enums\PredictionScope;
use App\Domain\Tournaments\Enums\PredictionStatus;
use App\Domain\Tournaments\Enums\PredictionVisibility;
use App\Domain\Tournaments\Enums\TeamType;
use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\PredictionScore;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $email = strtolower($request->user()->email);
        $currentTeam = $user->currentTeam;
        $tournament = $currentTeam ? Tournament::query()->find($currentTeam->id) : null;

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $tournamentTeams = $tournament?->tournamentTeams()
            ->orderBy('name')
            ->get()
            ->map(fn (TournamentTeam $tournamentTeam) => [
                'id' => $tournamentTeam->id,
                'name' => $tournamentTeam->name,
                'short_name' => $tournamentTeam->short_name,
                'type' => $tournamentTeam->type->value,
                'type_label' => ucfirst($tournamentTeam->type->value),
            ]) ?? collect();

        $matches = $tournament?->matches()
            ->with(['homeTournamentTeam', 'awayTournamentTeam'])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (TournamentMatch $match) => [
                'id' => $match->id,
                'home_tournament_team_id' => $match->home_tournament_team_id,
                'away_tournament_team_id' => $match->away_tournament_team_id,
                'home_team_name' => $match->homeTournamentTeam->name,
                'away_team_name' => $match->awayTournamentTeam->name,
                'starts_at' => $match->starts_at->toISOString(),
                'locks_at' => $match->locks_at?->toISOString(),
                'status' => $match->status->value,
                'status_label' => str_replace('_', ' ', ucfirst($match->status->value)),
                'venue' => $match->venue,
            ]) ?? collect();

        $predictionFields = $tournament?->predictionFields()
            ->with(['predictionResults.tournamentMatch.homeTournamentTeam', 'predictionResults.tournamentMatch.awayTournamentTeam'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (PredictionField $field) => [
                'id' => $field->id,
                'scope' => $field->scope->value,
                'field_type' => $field->field_type->value,
                'label' => $field->label,
                'description' => $field->description,
                'key' => $field->key,
                'visibility' => $field->visibility->value,
                'option_source' => $field->optionSource()?->value,
                'validation_schema' => $field->validation_schema,
                'scoring_strategy_key' => $field->scoring_strategy_key,
                'configuration' => $field->configuration,
                'is_active' => $field->is_active,
                'results' => $field->predictionResults
                    ->sortBy(fn (PredictionResult $result) => $result->tournament_match_id ?? 0)
                    ->values()
                    ->map(fn (PredictionResult $result) => [
                        'id' => $result->id,
                        'tournament_match_id' => $result->tournament_match_id,
                        'match_name' => $result->tournamentMatch
                            ? $result->tournamentMatch->homeTournamentTeam->name.' vs '.$result->tournamentMatch->awayTournamentTeam->name
                            : null,
                        'status' => $result->status->value,
                        'value' => $result->value,
                        'resolved_at' => $result->resolved_at?->toISOString(),
                    ]),
            ]) ?? collect();

        $participantSummary = null;
        $upcomingMatches = collect();
        $upcomingDeadlines = collect();

        if ($tournament !== null) {
            $scoreTotals = PredictionScore::query()
                ->select('user_id')
                ->selectRaw('SUM(points) as points')
                ->where('tournament_id', $tournament->id)
                ->groupBy('user_id');

            $rankedParticipants = $tournament->participants()
                ->select('users.id')
                ->leftJoinSub($scoreTotals, 'score_totals', fn ($join) => $join->on('users.id', '=', 'score_totals.user_id'))
                ->orderByRaw('COALESCE(score_totals.points, 0) DESC')
                ->orderBy('users.name')
                ->get();

            $rank = $rankedParticipants
                ->pluck('id')
                ->search($user->id);

            $currentPoints = (int) (PredictionScore::query()
                ->where('tournament_id', $tournament->id)
                ->where('user_id', $user->id)
                ->sum('points'));

            $tournamentFieldCount = (int) $tournament->predictionFields()
                ->where('scope', PredictionScope::Tournament->value)
                ->where('is_active', true)
                ->count();

            $matchFieldCount = (int) $tournament->predictionFields()
                ->where('scope', PredictionScope::Match->value)
                ->where('is_active', true)
                ->count();

            $matchCount = (int) $tournament->matches()->count();

            $submittedPredictionCount = (int) Prediction::query()
                ->where('tournament_id', $tournament->id)
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    PredictionStatus::Submitted->value,
                    PredictionStatus::Locked->value,
                ])
                ->count();

            $totalPredictionSlots = $tournamentFieldCount + ($matchFieldCount * $matchCount);
            $remainingPredictionCount = max($totalPredictionSlots - $submittedPredictionCount, 0);

            $participantSummary = [
                'rank' => $rank === false ? null : $rank + 1,
                'points' => $currentPoints,
                'submitted_predictions' => $submittedPredictionCount,
                'remaining_predictions' => $remainingPredictionCount,
            ];

            $upcomingMatches = $tournament->matches()
                ->with(['homeTournamentTeam:id,name', 'awayTournamentTeam:id,name'])
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')
                ->limit(6)
                ->get()
                ->map(fn (TournamentMatch $match): array => [
                    'id' => $match->id,
                    'home_team_name' => $match->homeTournamentTeam->name,
                    'away_team_name' => $match->awayTournamentTeam->name,
                    'starts_at' => $match->starts_at->toISOString(),
                    'locks_at' => $match->locks_at?->toISOString(),
                ])
                ->values();

            $upcomingDeadlines = $tournament->matches()
                ->with(['homeTournamentTeam:id,name', 'awayTournamentTeam:id,name'])
                ->whereNotNull('locks_at')
                ->where('locks_at', '>=', now())
                ->orderBy('locks_at')
                ->limit(6)
                ->get()
                ->map(fn (TournamentMatch $match): array => [
                    'id' => $match->id,
                    'match_name' => $match->homeTournamentTeam->name.' vs '.$match->awayTournamentTeam->name,
                    'locks_at' => $match->locks_at?->toISOString(),
                ])
                ->values();
        }

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'currentTeamSlug' => $currentTeam?->slug,
            'participantSummary' => $participantSummary,
            'upcomingMatches' => $upcomingMatches,
            'upcomingDeadlines' => $upcomingDeadlines,
            'tournamentTeams' => $tournamentTeams->values(),
            'matches' => $matches->values(),
            'predictionFields' => $predictionFields->values(),
            'teamTypes' => collect(TeamType::cases())
                ->map(fn (TeamType $type) => ['value' => $type->value, 'label' => ucfirst($type->value)])
                ->values(),
            'matchStatuses' => collect(MatchStatus::cases())
                ->map(fn (MatchStatus $status) => [
                    'value' => $status->value,
                    'label' => str_replace('_', ' ', ucfirst($status->value)),
                ])
                ->values(),
            'predictionScopes' => collect(PredictionScope::cases())
                ->map(fn (PredictionScope $scope) => ['value' => $scope->value, 'label' => ucfirst($scope->value)])
                ->values(),
            'predictionFieldTypes' => collect(PredictionFieldType::cases())
                ->map(fn (PredictionFieldType $type) => [
                    'value' => $type->value,
                    'label' => str_replace('_', ' ', ucfirst($type->value)),
                ])
                ->values(),
            'predictionVisibilities' => collect(PredictionVisibility::cases())
                ->map(fn (PredictionVisibility $visibility) => [
                    'value' => $visibility->value,
                    'label' => str_replace('_', ' ', ucfirst($visibility->value)),
                ])
                ->values(),
            'predictionResultStatuses' => collect(PredictionResultStatus::cases())
                ->map(fn (PredictionResultStatus $status) => [
                    'value' => $status->value,
                    'label' => ucfirst($status->value),
                ])
                ->values(),
            'canManageTournamentTeams' => $tournament
                ? Gate::forUser($user)->allows('create', [TournamentTeam::class, $tournament])
                : false,
            'canManageMatches' => $tournament
                ? Gate::forUser($user)->allows('create', [TournamentMatch::class, $tournament])
                : false,
            'canManagePredictionFields' => $tournament
                ? Gate::forUser($user)->allows('create', [PredictionField::class, $tournament])
                : false,
            'canResolvePredictionResults' => $tournament
                ? Gate::forUser($user)->allows('create', [PredictionResult::class, $tournament])
                : false,
        ]);
    }
}
