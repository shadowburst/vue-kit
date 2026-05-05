<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings\Team;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionTier;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Subscription;

final class BillingController extends Controller
{
    public function show(): Response
    {
        /** @var Team $team */
        $team = app('currentTeam');

        Gate::authorize('view', [Subscription::class, $team]);

        $tier     = $team->tier();
        $interval = null;

        if ($tier !== SubscriptionTier::Free) {
            $subscription = $team->subscription('default');

            if ($subscription !== null) {
                $priceId = $subscription->stripe_price;

                if ($priceId === SubscriptionTier::Pro->stripeMonthlyId()) {
                    $interval = SubscriptionInterval::Monthly->value;
                } elseif ($priceId === SubscriptionTier::Pro->stripeYearlyId()) {
                    $interval = SubscriptionInterval::Yearly->value;
                }
            }
        }

        return Inertia::render('settings/team/Billing', [
            'tier'     => $tier->value,
            'interval' => $interval,
        ]);
    }
}
