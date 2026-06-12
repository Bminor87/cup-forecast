<?php

namespace App\Domain\Tournaments\Policies;

use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentTeam;
use App\Enums\TeamRole;
use App\Models\User;

class TournamentTeamPolicy
{
    /**
     * Determine whether the user can view any tournament teams.
     */
    public function viewAny(User $user, Tournament $tournament): bool
    {
        return $this->userRole($user, $tournament) !== null;
    }

    /**
     * Determine whether the user can view the tournament team.
     */
    public function view(User $user, TournamentTeam $tournamentTeam): bool
    {
        return $this->userRole($user, $tournamentTeam->tournament) !== null;
    }

    /**
     * Determine whether the user can create tournament teams.
     */
    public function create(User $user, Tournament $tournament): bool
    {
        return $this->hasAtLeastRole($user, $tournament, TeamRole::Admin);
    }

    /**
     * Determine whether the user can update the tournament team.
     */
    public function update(User $user, TournamentTeam $tournamentTeam): bool
    {
        return $this->hasAtLeastRole($user, $tournamentTeam->tournament, TeamRole::Admin);
    }

    /**
     * Determine whether the user can delete the tournament team.
     */
    public function delete(User $user, TournamentTeam $tournamentTeam): bool
    {
        return $this->hasAtLeastRole($user, $tournamentTeam->tournament, TeamRole::Admin);
    }

    /**
     * Get the user's role for a tournament.
     */
    protected function userRole(User $user, Tournament $tournament): ?TeamRole
    {
        return $user->teamMemberships()
            ->where('team_id', $tournament->id)
            ->first()
            ?->role;
    }

    /**
     * Determine if the user has at least the required role for a tournament.
     */
    protected function hasAtLeastRole(User $user, Tournament $tournament, TeamRole $requiredRole): bool
    {
        $role = $this->userRole($user, $tournament);

        return $role?->isAtLeast($requiredRole) ?? false;
    }
}
