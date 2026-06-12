<?php

namespace App\Domain\Tournaments\Policies;

use App\Domain\Tournaments\Models\PredictionField;
use App\Domain\Tournaments\Models\Tournament;
use App\Enums\TeamRole;
use App\Models\User;

class PredictionFieldPolicy
{
    public function viewAny(User $user, Tournament $tournament): bool
    {
        return $this->userRole($user, $tournament) !== null;
    }

    public function view(User $user, PredictionField $predictionField): bool
    {
        return $this->userRole($user, $predictionField->tournament) !== null;
    }

    public function create(User $user, Tournament $tournament): bool
    {
        return $this->hasAtLeastRole($user, $tournament, TeamRole::Admin);
    }

    public function update(User $user, PredictionField $predictionField): bool
    {
        return $this->hasAtLeastRole($user, $predictionField->tournament, TeamRole::Admin);
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
