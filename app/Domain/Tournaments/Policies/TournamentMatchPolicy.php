<?php

namespace App\Domain\Tournaments\Policies;

use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Enums\TeamRole;
use App\Models\User;

class TournamentMatchPolicy
{
    /**
     * Determine whether the user can view any matches for a tournament.
     */
    public function viewAny(User $user, Tournament $tournament): bool
    {
        return $this->userRole($user, $tournament) !== null;
    }

    /**
     * Determine whether the user can view the match.
     */
    public function view(User $user, TournamentMatch $match): bool
    {
        return $this->userRole($user, $match->tournament) !== null;
    }

    /**
     * Determine whether the user can create matches.
     */
    public function create(User $user, Tournament $tournament): bool
    {
        return $this->hasAtLeastRole($user, $tournament, TeamRole::Admin);
    }

    /**
     * Determine whether the user can update the match.
     */
    public function update(User $user, TournamentMatch $match): bool
    {
        return $this->hasAtLeastRole($user, $match->tournament, TeamRole::Admin);
    }

    /**
     * Determine whether the user can delete the match.
     */
    public function delete(User $user, TournamentMatch $match): bool
    {
        return $this->hasAtLeastRole($user, $match->tournament, TeamRole::Admin);
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
