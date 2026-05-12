<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Spatie\QueueableAction\QueueableAction;

final class CancelSubscription
{
    use QueueableAction;

    public function execute(Subscription $subscription, ?User $operator = null): void
    {
        /** @var Team $team */
        $team = $subscription->owner;

        $freeMemberCap = Config::integer('billing.tiers.free.member_cap');
        $nonOwnerCount = $team->members()->whereKeyNot($team->owner_id)->count();

        if ($nonOwnerCount > $freeMemberCap) {
            throw new RuntimeException(
                'Cannot cancel: team exceeds the Free tier member cap. Remove members first.'
            );
        }

        $subscription->cancel();

        /** @var User|null $causer */
        $causer = $operator ?? auth()->user();

        $builder = ($operator instanceof User ? activity('admin') : activity())
            ->performedOn($subscription)
            ->withProperties(['team_id' => $team->id]);

        if ($causer instanceof User) {
            $builder = $builder->causedBy($causer);
        }

        $builder->log('subscription.cancel.period_end');
    }
}
