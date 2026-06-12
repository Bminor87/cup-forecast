<?php

namespace App\Domain\Tournaments\Policies;

use App\Domain\Tournaments\Models\Player;
use App\Domain\Tournaments\Models\Tournament;
use App\Enums\TeamRole;
use App\Models\User;

class PlayerPolicy
{
    /**
     * Determine whether the user can view any players for a tournament.
     */
    public function viewAny(User $user, Tournament $tournament): bool
    {
        return $this->userRole($user, $tournament) !== null;
    }

    /**
     * Determine whether the user can view a player.
     */
    public function view(User $user, Player $player): bool
    {
        return $this->userRole($user, $player->tournament) !== null;
    }

    /**
     * Determine whether the user can create players.
     */
    public function create(User $user, Tournament $tournament): bool
    {
        return $this->hasAtLeastRole($user, $tournament, TeamRole::Admin);
    }

    /**
     * Determine whether the user can update a player.
     */
    public function update(User $user, Player $player): bool
    {
        return $this->hasAtLeastRole($user, $player->tournament, TeamRole::Admin);
    }

    /**
     * Determine whether the user can delete a player.
     */
    public function delete(User $user, Player $player): bool
    {
        return $this->hasAtLeastRole($user, $player->tournament, TeamRole::Admin);
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
