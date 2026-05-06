<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings\Team;

use App\Enums\Subscription\SubscriptionInterval;
use App\Enums\Subscription\SubscriptionTier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\Team\CheckoutRequest;
use App\Services\Team\TeamContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Laravel\Cashier\Subscription;

final class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, TeamContext $teamContext): RedirectResponse
    {
        $team = $teamContext->currentOrFail();

        Gate::authorize('update', [Subscription::class, $team]);

        $interval = SubscriptionInterval::from((string) $request->validated('interval'));

        $priceId = $interval === SubscriptionInterval::Monthly
            ? SubscriptionTier::Pro->stripeMonthlyId()
            : SubscriptionTier::Pro->stripeYearlyId();

        if ($priceId === null) {
            abort(500, 'Stripe price ID not configured for the selected interval.');
        }

        $successUrl = route('teams.billing.show', ['checkout' => 'success']);
        $cancelUrl  = route('teams.billing.show');

        return $team->checkout($priceId, [
            'mode'                  => 'subscription',
            'success_url'           => $successUrl,
            'cancel_url'            => $cancelUrl,
            'allow_promotion_codes' => true,
        ])->redirect();
    }
}
