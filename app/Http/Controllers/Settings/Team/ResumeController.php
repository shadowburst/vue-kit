<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings\Team;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Team\TeamContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ResumeController extends Controller
{
    public function store(TeamContext $teamContext): RedirectResponse
    {
        $team = $teamContext->currentOrFail();

        Gate::authorize('resume', [Subscription::class, $team]);

        $subscription = $team->subscription('default');

        if ($subscription !== null && $subscription->onGracePeriod()) {
            $subscription->resume();
        }

        return redirect()->route('teams.billing.show');
    }
}
