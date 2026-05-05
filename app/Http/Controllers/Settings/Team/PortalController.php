<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings\Team;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Laravel\Cashier\Subscription;

final class PortalController extends Controller
{
    public function show(): RedirectResponse
    {
        /** @var Team $team */
        $team = app('currentTeam');

        Gate::authorize('update', [Subscription::class, $team]);

        return $team->redirectToBillingPortal(route('teams.billing.show', ['portal' => 'return']));
    }
}
