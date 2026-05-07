<?php

declare(strict_types=1);

namespace App\Actions\Membership;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueueableAction\QueueableAction;

final class RemoveMembership
{
    use QueueableAction;

    public function execute(User $user, Team $team): void
    {
        $registrar      = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        try {
            DB::transaction(function () use ($registrar, $user, $team): void {
                $registrar->setPermissionsTeamId($team->id);
                $user->syncRoles([]);
                (new ResetCurrentTeam)->execute($user, $team);
            });
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }
}
