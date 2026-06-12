<?php

namespace App\Domain\Tournaments\Policies;

use App\Domain\Tournaments\Models\Tournament;
use App\Enums\TeamRole;
use App\Models\User;

class TournamentPolicy
{
    /**
     * Determine whether the user can view any tournaments.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a tournament.
     */
    public function view(User $user, Tournament $tournament): bool
    {
        return $this->userRole($user, $tournament) !== null;
    }

    /**
     * Determine whether the user can create tournaments.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update tournament settings.
     */
    public function update(User $user, Tournament $tournament): bool
    {
        return $this->hasAtLeastRole($user, $tournament, TeamRole::Admin);
    }

    /**
     * Determine whether the user can invite participants.
     */
    public function inviteParticipant(User $user, Tournament $tournament): bool
    {
        return $this->hasAtLeastRole($user, $tournament, TeamRole::Admin);
    }

    /**
     * Determine whether the user can manage participant roles.
     */
    public function manageParticipants(User $user, Tournament $tournament): bool
    {
        return $this->hasAtLeastRole($user, $tournament, TeamRole::Admin);
    }

    /**
     * Determine whether the user can delete the tournament.
     */
    public function delete(User $user, Tournament $tournament): bool
    {
        return ! $tournament->is_personal && $this->hasAtLeastRole($user, $tournament, TeamRole::Owner);
    }

    /**
     * Get the user's role for the tournament.
     */
    protected function userRole(User $user, Tournament $tournament): ?TeamRole
    {
        return $user->teamMemberships()
            ->where('team_id', $tournament->id)
            ->first()
            ?->role;
    }

    /**
     * Determine if the user has at least the given role for the tournament.
     */
    protected function hasAtLeastRole(User $user, Tournament $tournament, TeamRole $requiredRole): bool
    {
        $role = $this->userRole($user, $tournament);

        return $role?->isAtLeast($requiredRole) ?? false;
    }
}
