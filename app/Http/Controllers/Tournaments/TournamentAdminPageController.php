<?php

namespace App\Http\Controllers\Tournaments;

use App\Domain\Tournaments\Enums\MatchStatus;
use App\Domain\Tournaments\Enums\PlayerPosition;
use App\Domain\Tournaments\Enums\PredictionVisibility;
use App\Domain\Tournaments\Enums\TeamType;
use App\Domain\Tournaments\Models\Player;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\PredictionResult;
use App\Domain\Tournaments\Models\PredictionScore;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Domain\Tournaments\PredictionFieldTemplateCatalog;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TournamentAdminPageController extends Controller
{
    public function teams(Request $request, Team $team): Response
    {
        $tournament = Tournament::query()->findOrFail($team->id);

        Gate::authorize('viewAny', [TournamentTeam::class, $tournament]);

        return Inertia::render('teams/admin/teams', [
            'teamSlug' => $team->slug,
            'tournamentTeams' => $tournament->tournamentTeams()
                ->orderBy('name')
                ->get()
                ->map(fn (TournamentTeam $tournamentTeam): array => [
                    'id' => $tournamentTeam->id,
                    'name' => $tournamentTeam->name,
                    'short_name' => $tournamentTeam->short_name,
                    'type' => $tournamentTeam->type->value,
                    'type_label' => ucfirst($tournamentTeam->type->value),
                ])
                ->values(),
            'teamTypes' => collect(TeamType::cases())
                ->map(fn (TeamType $type): array => ['value' => $type->value, 'label' => ucfirst($type->value)])
                ->values(),
            'canManageTournamentTeams' => Gate::forUser($request->user())->allows('create', [TournamentTeam::class, $tournament]),
        ]);
    }

    public function players(Request $request, Team $team): Response
    {
        $tournament = Tournament::query()->findOrFail($team->id);

        Gate::authorize('viewAny', [Player::class, $tournament]);

        return Inertia::render('teams/admin/players', [
            'teamSlug' => $team->slug,
            'tournamentTeams' => $tournament->tournamentTeams()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values(),
            'players' => $tournament->players()
                ->with('tournamentTeam:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (Player $player): array => [
                    'id' => $player->id,
                    'name' => $player->name,
                    'short_name' => $player->short_name,
                    'shirt_number' => $player->shirt_number,
                    'position' => $player->position?->value,
                    'team_name' => $player->tournamentTeam->name,
                ])
                ->values(),
            'positions' => collect(PlayerPosition::cases())
                ->map(fn (PlayerPosition $position): array => [
                    'value' => $position->value,
                    'label' => str_replace('_', ' ', ucfirst($position->value)),
                ])
                ->values(),
            'canManagePlayers' => Gate::forUser($request->user())->allows('create', [Player::class, $tournament]),
        ]);
    }

    public function matches(Request $request, Team $team): Response
    {
        $tournament = Tournament::query()->findOrFail($team->id);

        Gate::authorize('viewAny', [TournamentMatch::class, $tournament]);

        return Inertia::render('teams/admin/matches', [
            'teamSlug' => $team->slug,
            'tournamentTeams' => $tournament->tournamentTeams()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values(),
            'matches' => $tournament->matches()
                ->with(['homeTournamentTeam:id,name', 'awayTournamentTeam:id,name'])
                ->orderBy('starts_at')
                ->get()
                ->map(fn (TournamentMatch $match): array => [
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
                ])
                ->values(),
            'matchStatuses' => collect(MatchStatus::cases())
                ->map(fn (MatchStatus $status): array => [
                    'value' => $status->value,
                    'label' => str_replace('_', ' ', ucfirst($status->value)),
                ])
                ->values(),
            'canManageMatches' => Gate::forUser($request->user())->allows('create', [TournamentMatch::class, $tournament]),
        ]);
    }

    public function predictionQuestions(Request $request, Team $team, PredictionFieldTemplateCatalog $templateCatalog): Response
    {
        $tournament = Tournament::query()->findOrFail($team->id);

        Gate::authorize('viewAny', [PredictionField::class, $tournament]);

        return Inertia::render('teams/admin/prediction-questions', [
            'teamSlug' => $team->slug,
            'templates' => $templateCatalog->templates(),
            'predictionFields' => $tournament->predictionFields()
                ->orderBy('scope')
                ->orderBy('created_at')
                ->get()
                ->map(fn (PredictionField $field): array => [
                    'id' => $field->id,
                    'scope' => $field->scope->value,
                    'field_type' => $field->field_type->value,
                    'option_source' => $field->optionSource()?->value,
                    'label' => $field->label,
                    'description' => $field->description,
                    'key' => $field->key,
                    'visibility' => $field->visibility->value,
                    'validation_schema' => $field->validation_schema,
                    'scoring_strategy_key' => $field->scoring_strategy_key,
                    'configuration' => $field->configuration,
                    'is_active' => $field->is_active,
                    'result_count' => $field->predictionResults()->count(),
                ])
                ->values(),
            'predictionVisibilities' => collect(PredictionVisibility::cases())
                ->map(fn (PredictionVisibility $visibility): array => [
                    'value' => $visibility->value,
                    'label' => str_replace('_', ' ', ucfirst($visibility->value)),
                ])
                ->values(),
            'canManagePredictionFields' => Gate::forUser($request->user())->allows('create', [PredictionField::class, $tournament]),
            'canResolvePredictionResults' => Gate::forUser($request->user())->allows('create', [PredictionResult::class, $tournament]),
        ]);
    }

    public function participants(Request $request, Team $team): Response
    {
        $tournament = Tournament::query()->findOrFail($team->id);

        Gate::authorize('viewAny', [PredictionField::class, $tournament]);

        $scoreTotals = PredictionScore::query()
            ->select('user_id')
            ->selectRaw('SUM(points) as points')
            ->where('tournament_id', $tournament->id)
            ->groupBy('user_id');

        $participants = $tournament->participants()
            ->select('users.id', 'users.name', 'users.email', 'users.avatar')
            ->leftJoinSub($scoreTotals, 'score_totals', fn ($join) => $join->on('users.id', '=', 'score_totals.user_id'))
            ->orderByRaw('COALESCE(score_totals.points, 0) DESC')
            ->orderBy('users.name')
            ->get()
            ->map(fn ($user, int $index): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'rank' => $index + 1,
                'points' => (int) ($user->points ?? 0),
            ])
            ->values();

        return Inertia::render('teams/admin/participants', [
            'teamSlug' => $team->slug,
            'participants' => $participants,
        ]);
    }
}
