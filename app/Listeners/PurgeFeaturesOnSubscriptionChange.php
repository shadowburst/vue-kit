<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Membership\PlanMembershipPrune;
use App\Actions\Membership\RemoveMembership;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Pennant\Feature;

final class PurgeFeaturesOnSubscriptionChange
{
    private const SUBSCRIPTION_EVENTS = [
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted',
    ];

    public function handle(WebhookHandled $event): void
    {
        if (! in_array($event->payload['type'], self::SUBSCRIPTION_EVENTS, true)) {
            return;
        }

        $stripeId = $event->payload['data']['object']['customer'] ?? null;

        if (! is_string($stripeId)) {
            return;
        }

        $team = Team::query()->where('stripe_id', $stripeId)->first();

        if (! $team instanceof Team) {
            return;
        }

        if ($event->payload['type'] === 'customer.subscription.deleted') {
            /** @mago-expect analysis:mixed-assignment */
            $reason = $event->payload['data']['object']['cancellation_details']['reason'] ?? null;

            // Only voluntary owner-initiated cancellation triggers the prune.
            // Payment-failure cancellations leave the team over-cap-but-intact.
            if ($reason === 'cancellation_requested') {
                $this->pruneOverCapMembers($team);
            }
        }

        $scope = Feature::serializeScope($team);
        $table = (string) config('pennant.stores.database.table', 'features');

        /** @var list<string> $stored */
        $stored = DB::table($table)
            ->where('scope', $scope)
            ->pluck('name')
            ->all();

        if ($stored !== []) {
            Feature::for($team)->forget($stored);
        }
    }

    private function pruneOverCapMembers(Team $team): void
    {
        $newCap        = (int) config('billing.tiers.free.member_cap', 0);
        $usersToDetach = (new PlanMembershipPrune)->execute($team, $newCap);

        if ($usersToDetach->isEmpty()) {
            return;
        }

        $remove = new RemoveMembership;

        foreach ($usersToDetach as $user) {
            $remove->execute($user, $team);
        }
    }
}
