<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Spatie\QueueableAction\QueueableAction;

final class CancelSubscription
{
    use QueueableAction;

    public function execute(Subscription $subscription, ?User $operator = null): void
    {
        $team = $subscription->owner;

        if (! $team instanceof Team) {
            throw new RuntimeException('Subscription is not attached to a team.');
        }

        $freeMemberCap = Config::integer('billing.tiers.free.member_cap');
        $nonOwnerCount = (int) $team->members()->whereKeyNot($team->owner_id)->count();

        if ($nonOwnerCount > $freeMemberCap) {
            throw new RuntimeException(
                'Cannot cancel: team exceeds the Free tier member cap. Remove members first.',
            );
        }

        $subscription->cancel();

        /** @var User|null $causer */
        $causer = $operator ?? Auth::user();

        $builder = ($operator !== null ? activity('admin') : activity())
            ->performedOn($subscription)
            ->withProperties(['team_id' => $team->id]);

        if ($causer !== null) {
            $builder = $builder->causedBy($causer);
        }

        $builder->log('subscription.cancel.period_end');
    }
}
