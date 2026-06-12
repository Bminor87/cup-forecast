<?php

namespace App\Domain\Tournaments\Policies;

use App\Domain\Tournaments\Models\Prediction;
use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\Tournament;
use App\Enums\TeamRole;
use App\Models\User;

class PredictionPolicy
{
    public function viewAny(User $user, Tournament $tournament): bool
    {
        return $this->userRole($user, $tournament) !== null;
    }

    public function view(User $user, Prediction $prediction): bool
    {
        if ($prediction->user_id === $user->id) {
            return true;
        }

        return $this->hasAtLeastRole($user, $prediction->tournament, TeamRole::Admin);
    }

    public function create(User $user, Tournament|PredictionField $target): bool
    {
        $tournament = $target instanceof PredictionField
            ? $target->tournament
            : $target;

        return $this->userRole($user, $tournament) !== null;
    }

    public function update(User $user, Prediction $prediction): bool
    {
        if ($prediction->user_id === $user->id) {
            return true;
        }

        return $this->hasAtLeastRole($user, $prediction->tournament, TeamRole::Admin);
    }

    protected function userRole(User $user, Tournament $tournament): ?TeamRole
    {
        return $user->teamMemberships()
            ->where('team_id', $tournament->id)
            ->first()
            ?->role;
    }

    protected function hasAtLeastRole(User $user, Tournament $tournament, TeamRole $requiredRole): bool
    {
        $role = $this->userRole($user, $tournament);

        return $role?->isAtLeast($requiredRole) ?? false;
    }
}
