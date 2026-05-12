<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\Role\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\QueueableAction\QueueableAction;

final class RevokeAdminRole
{
    use QueueableAction;

    public function execute(User $target, User $operator): void
    {
        if (! $operator->canRevokeAdminRole($target)) {
            throw new RuntimeException('An operator cannot revoke their own admin role.');
        }

        $adminRoleId = DB::table('roles')
            ->where('name', Role::Admin->value)
            ->where('guard_name', 'web')
            ->value('id');

        if ($adminRoleId !== null) {
            DB::table('model_has_roles')
                ->where('model_id', $target->getKey())
                ->where('model_type', $target->getMorphClass())
                ->where('role_id', $adminRoleId)
                ->delete();
        }

        activity('admin')
            ->causedBy($operator)
            ->performedOn($target)
            ->log('admin.revoke');
    }
}
