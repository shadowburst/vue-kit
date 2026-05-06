<?php

declare(strict_types=1);

namespace App\Actions\Membership;

use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueueableAction\QueueableAction;

final class AssignMembership
{
    use QueueableAction;

    public function execute(User $user, Team $team, Role $role): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        try {
            DB::transaction(function () use ($registrar, $user, $team, $role): void {
                $registrar->setPermissionsTeamId($team->id);
                $user->assignRole($role->value);
            });
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }
}
