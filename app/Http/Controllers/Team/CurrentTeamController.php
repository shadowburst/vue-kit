<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CurrentTeamController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'team_id' => ['required', 'integer', 'exists:teams,id'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! $user->teams()->whereKey($validated['team_id'])->exists()) {
            abort(403);
        }

        $user->update(['current_team_id' => $validated['team_id']]);

        return back();
    }
}
