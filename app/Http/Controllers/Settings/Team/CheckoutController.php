<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings\Team;

use App\Data\Billing\TeamCheckoutRequest;
use App\Enums\Subscription\SubscriptionInterval;
use App\Enums\Subscription\SubscriptionTier;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Team\TeamContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class CheckoutController extends Controller
{
    public function store(TeamCheckoutRequest $request, TeamContext $teamContext): RedirectResponse
    {
        $team = $teamContext->currentOrFail();

        Gate::authorize('create', [Subscription::class, $team]);

        $priceId = $request->interval === SubscriptionInterval::Monthly
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
