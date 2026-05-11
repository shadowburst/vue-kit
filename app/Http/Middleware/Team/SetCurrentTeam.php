<?php

declare(strict_types=1);

namespace App\Http\Middleware\Team;

use App\Actions\Membership\ResetCurrentTeam;
use App\Models\Team;
use App\Models\User;
use App\Services\Team\TeamContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

final class SetCurrentTeam
{
    public function __construct(
        private readonly ResetCurrentTeam $resetCurrentTeam,
        private readonly TeamContext $teamContext,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || $request->routeIs('teams.create', 'teams.index', 'teams.store')) {
            return $next($request);
        }

        $team = $this->resolveTeam($user);

        if ($team === null) {
            return redirect()->route('teams.create');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
        $this->teamContext->setTeam($team);

        return $next($request);
    }

    private function resolveTeam(User $user): ?Team
    {
        $team = $this->resolveFromCurrentTeamId($user);

        if ($team === null) {
            $this->resetCurrentTeam->execute($user);
            $user->refresh();
            $team = $this->resolveFromCurrentTeamId($user);
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
}
