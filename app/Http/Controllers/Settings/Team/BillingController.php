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

        $tier               = $team->tier();
        $interval           = null;
        $subscriptionStatus = null;
        $pmLastFour         = null;
        $nextChargeDate     = null;
        $nextChargeAmount   = null;

        if ($tier !== SubscriptionTier::Free) {
            $subscription = $team->subscription('default');

            if ($subscription !== null) {
                $priceId = $subscription->stripe_price;

                if ($priceId !== null) {
                    $interval = match (true) {
                        $priceId === SubscriptionTier::Pro->stripeMonthlyId() => SubscriptionInterval::Monthly->value,
                        $priceId === SubscriptionTier::Pro->stripeYearlyId()  => SubscriptionInterval::Yearly->value,
                        default                                                => null,
                    };
                }

                $subscriptionStatus = $subscription->onGracePeriod() ? 'grace' : 'active';
            }

            $pmLastFour = $team->pm_last_four;

            if ($subscription !== null && ! $subscription->onGracePeriod()) {
                $upcomingInvoice = $team->upcomingInvoice();

                if ($upcomingInvoice !== null) {
                    $nextChargeDate   = $upcomingInvoice->date()?->toDateString();
                    $nextChargeAmount = $upcomingInvoice->total();
                }
            }
        }

        return Inertia::render('settings/team/Billing', [
            'tier'               => $tier->value,
            'interval'           => $interval,
            'subscriptionStatus' => $subscriptionStatus,
            'pmLastFour'         => $pmLastFour,
            'nextChargeDate'     => $nextChargeDate,
            'nextChargeAmount'   => $nextChargeAmount,
        ]);
    }
}
