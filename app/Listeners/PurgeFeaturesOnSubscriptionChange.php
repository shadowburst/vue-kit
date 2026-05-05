<?php

declare(strict_types=1);

namespace App\Listeners;

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

        if ($stripeId === null) {
            return;
        }

        $team = Team::where('stripe_id', $stripeId)->first();

        if ($team === null) {
            return;
        }

        $scope = Feature::serializeScope($team);
        $table = config('pennant.stores.database.table', 'features');

        /** @var list<string> $stored */
        $stored = DB::table($table)
            ->where('scope', $scope)
            ->pluck('name')
            ->all();

        if ($stored !== []) {
            Feature::for($team)->forget($stored);
        }
    }
}
