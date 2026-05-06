<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission\Permission;
use App\Models\Team;
use App\Models\User;

final class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $user->can(Permission::TeamView->value);
    }

    public function update(User $user, Team $team): bool
    {
        return $user->can(Permission::TeamUpdate->value);
    }

    public function delete(User $user, Team $team): bool
    {
        return (
            $user->can(Permission::TeamDelete->value)
            && $user->ownedTeams()->where('teams.id', '!=', $team->id)->exists()
        );
    }
}
