<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Team;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

final class SetCurrentTeam
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || $request->routeIs('teams.create')) {
            return $next($request);
        }

        $team = $this->resolveTeam($user);

        if ($team === null) {
            return redirect()->route('teams.create');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
        app()->instance('currentTeam', $team);

        return $next($request);
    }

    private function resolveTeam(User $user): ?Team
    {
        $team = $this->resolveFromCurrentTeamId($user);

        if ($team === null) {
            $team = $this->resolveFromFirstAvailableTeam($user);
        }

        return $team;
    }

    /** @mago-expect analysis:less-specific-return-statement */
    private function resolveFromCurrentTeamId(User $user): ?Team
    {
        if ($user->current_team_id === null) {
            return null;
        }

        /** @var Team|null $team */
        return $user->teams()->find($user->current_team_id);
    }

    private function resolveFromFirstAvailableTeam(User $user): ?Team
    {
        /** @var Team|null $team */
        $team = $user->teams()->first();

        if ($team !== null) {
            $user->update(['current_team_id' => $team->id]);
        }

        return $team;
    }
}
