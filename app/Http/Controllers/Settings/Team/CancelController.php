<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings\Team;

use App\Http\Controllers\Controller;
use App\Services\Team\TeamContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Laravel\Cashier\Subscription;

final class CancelController extends Controller
{
    public function store(TeamContext $teamContext): RedirectResponse
    {
        $team = $teamContext->currentOrFail();

        Gate::authorize('cancel', [Subscription::class, $team]);

        $team->subscription('default')?->cancel();

        return redirect()->route('teams.billing.show');
    }
}
