<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission\Permission;
use App\Models\Team;
use App\Models\User;

final class SubscriptionPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $user->can(Permission::SubscriptionView->value);
    }

    public function update(User $user, Team $team): bool
    {
        return $user->can(Permission::SubscriptionUpdate->value);
    }
}
