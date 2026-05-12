<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings\Team;

use App\Actions\Admin\CancelSubscription;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Team\TeamContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class CancelController extends Controller
{
    public function store(TeamContext $teamContext): RedirectResponse
    {
        $team = $teamContext->currentOrFail();

        Gate::authorize('cancel', [Subscription::class, $team]);

        $subscription = $team->subscription('default');

        if ($subscription !== null) {
            if (! $subscription instanceof Subscription) {
                return redirect()->route('teams.billing.show');
            }

            app(CancelSubscription::class)->execute($subscription);
        }

        return redirect()->route('teams.billing.show');
    }
}
