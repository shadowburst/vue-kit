<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Feature\Feature as FeatureEnum;
use App\Enums\Permission\Permission;
use App\Models\User;
use App\Services\Team\TeamContext;
use Laravel\Pennant\Feature;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::UserViewAny->value);
    }

    public function view(User $user, User $target): bool
    {
        return $user->can(Permission::UserView->value);
    }

    public function create(User $user): bool
    {
        if (! $user->can(Permission::UserCreate->value)) {
            return false;
        }

        $team = app(TeamContext::class)->current();

        if ($team === null) {
            return false;
        }

        $cap           = (int) Feature::for($team)->value(FeatureEnum::TeamMemberCap->value);
        $nonOwnerCount = $team->members()->whereKeyNot($team->owner_id)->count();

        return $nonOwnerCount < $cap;
    }

    public function update(User $user, User $target): bool
    {
        return $user->can(Permission::UserUpdate->value);
    }

    public function delete(User $user, User $target): bool
    {
        if ($target->is($user)) {
            return true;
        }

        return $user->can(Permission::UserDelete->value);
    }
}
