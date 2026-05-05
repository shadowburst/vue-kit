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
        DB::transaction(function () use ($user, $team, $role): void {
            app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
            $user->assignRole($role->value);
        });
    }
}
