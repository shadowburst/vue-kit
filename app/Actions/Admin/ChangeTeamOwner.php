<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueueableAction\QueueableAction;

final class ChangeTeamOwner
{
    use QueueableAction;

    public function execute(Team $team, User $newOwner, ?User $operator = null): void
    {
        $oldOwnerId     = $team->owner_id;
        $team->owner_id = $newOwner->id;
        $team->save();

        $registrar = app(PermissionRegistrar::class);
        $previous  = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($team->id);

            if (! $newOwner->hasRole(Role::Manager->value)) {
                $newOwner->assignRole(Role::Manager->value);
            }
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }

        $builder = activity('admin')
            ->performedOn($team)
            ->withProperties(['old_owner_id' => $oldOwnerId, 'new_owner_id' => $newOwner->id]);

        if ($operator !== null) {
            $builder = $builder->causedBy($operator);
        }

        $builder->log('team.owner.change');
    }
}
