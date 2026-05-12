<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings\Team;

use App\Data\Billing\TeamBillingProps;
use App\Enums\Subscription\SubscriptionInterval;
use App\Enums\Subscription\SubscriptionTier;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Team\TeamContext;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class BillingController extends Controller
{
    public function show(TeamContext $teamContext): Response
    {
        $team = $teamContext->currentOrFail();

        Gate::authorize('view', [Subscription::class, $team]);

        $tier               = $team->tier;
        $interval           = null;
        $subscriptionStatus = null;
        $pmLastFour         = null;
        $nextChargeDate     = null;
        $nextChargeAmount   = null;

        if ($tier !== SubscriptionTier::Free) {
            $subscription = $team->subscription('default');

            if ($subscription !== null) {
                $priceId = $subscription->getAttribute('stripe_price');

                if (is_string($priceId)) {
                    $interval = match (true) {
                        $priceId === SubscriptionTier::Pro->stripeMonthlyId() => SubscriptionInterval::Monthly->value,
                        $priceId === SubscriptionTier::Pro->stripeYearlyId() => SubscriptionInterval::Yearly->value,
                        default => null,
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

        return Inertia::render('settings/team/Billing', new TeamBillingProps(
            tier              : $tier,
            interval          : $interval,
            subscriptionStatus: $subscriptionStatus,
            pmLastFour        : $pmLastFour,
            nextChargeDate    : $nextChargeDate,
            nextChargeAmount  : $nextChargeAmount,
        ));
    }
}
