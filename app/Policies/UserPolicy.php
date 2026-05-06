<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission\Permission;
use App\Models\User;

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
        return $user->can(Permission::UserCreate->value);
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
