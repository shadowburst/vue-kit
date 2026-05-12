<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueueableAction\QueueableAction;

final class GrantAdminRole
{
    use QueueableAction;

    public function execute(User $target, ?User $operator = null): void
    {
        /** @var Team|null $team */
        $team = $target->ownedTeams()->first();

        if ($team === null) {
            throw new RuntimeException('User must own at least one team to be granted the admin role.');
        }

        $registrar  = app(PermissionRegistrar::class);
        $previousId = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($team->id);
            $target->assignRole(Role::Admin->value);
        } finally {
            $registrar->setPermissionsTeamId($previousId);
        }

        $builder = activity('admin')->performedOn($target);

        if ($operator !== null) {
            $builder = $builder->causedBy($operator);
        }

        $builder->log('admin.grant');
    }
}
