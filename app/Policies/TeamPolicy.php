<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Team;
use App\Models\User;

final class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $user->can(PermissionName::TeamView->value);
    }

    public function update(User $user, Team $team): bool
    {
        return $user->can(PermissionName::TeamUpdate->value);
    }

    public function delete(User $user, Team $team): bool
    {
        return (
            $user->can(PermissionName::TeamDelete->value)
            && $user->ownedTeams()->where('teams.id', '!=', $team->id)->exists()
        );
    }
}
