<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission\Permission;
use App\Enums\Subscription\SubscriptionTier;
use App\Models\Team;
use App\Models\User;

final class SubscriptionPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $user->can(Permission::SubscriptionView->value);
    }

    public function create(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }

    public function cancel(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id && $team->canTransitionTo(SubscriptionTier::Free);
    }

    public function resume(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }

    /**
     * Authorize owner-driven subscription management surfaces such as the
     * Stripe billing portal. Distinct from the pre-#69 `update` umbrella,
     * which has been split into `cancel` and `resume`; this `update` only
     * covers Stripe-hosted management flows.
     */
    public function update(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }
}
