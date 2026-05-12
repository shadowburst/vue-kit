<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings\Team;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Team\TeamContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class PortalController extends Controller
{
    public function show(TeamContext $teamContext): RedirectResponse
    {
        $team = $teamContext->currentOrFail();

        Gate::authorize('update', [Subscription::class, $team]);

        return $team->redirectToBillingPortal(route('teams.billing.show', ['portal' => 'return']));
    }
}
