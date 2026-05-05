<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Events\RoleDetachedEvent;
use Spatie\Permission\PermissionRegistrar;

final class SyncCurrentTeamOnRoleDetached
{
    public function handle(RoleDetachedEvent $event): void
    {
        $user = $event->model;

        if (! $user instanceof User) {
            return;
        }

        /** @var int|string|null $teamId */
        $teamId = app(PermissionRegistrar::class)->getPermissionsTeamId();

        if ($teamId === null) {
            return;
        }

        // If the user still holds at least one role in this team, they remain a member.
        $stillHasRoles = DB::table('model_has_roles')
            ->where('model_type', $user->getMorphClass())
            ->where('model_id', $user->id)
            ->where('team_id', $teamId)
            ->exists();

        if ($stillHasRoles) {
            return;
        }

        // Membership gone — only touch current_team_id if it pointed at this team.
        if ((int) $user->current_team_id !== (int) $teamId) {
            return;
        }

        $newTeamId = $user->teams()->where('teams.id', '!=', $teamId)->first()?->id;
        $user->update(['current_team_id' => $newTeamId]);
    }
}
