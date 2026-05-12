<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource;

use App\Enums\Role\Role;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class UserResourceQueries
{
    /** @return array<string> */
    public static function roleNames(User $user): array
    {
        $roles = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->getKey())
            ->where('model_has_roles.model_type', $user->getMorphClass())
            ->distinct()
            ->pluck('roles.name')
            ->toArray();

        $names = [];

        foreach ($roles as $role) {
            if (is_string($role)) {
                $names[] = $role;
            }
        }

        return $names;
    }

    public static function isAdmin(User $user): bool
    {
        return DB::table(Config::string('permission.table_names.model_has_roles', 'model_has_roles'))
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->getKey())
            ->where('model_has_roles.model_type', $user->getMorphClass())
            ->where('roles.name', Role::Admin->value)
            ->where('roles.guard_name', 'web')
            ->exists();
    }

    public static function canImpersonate(User $user): bool
    {
        return ! self::isAdmin($user) && ! $user->trashed();
    }

    public static function hasMemberships(User $user): bool
    {
        return DB::table(Config::string('permission.table_names.model_has_roles', 'model_has_roles'))
            ->where('model_id', $user->getKey())
            ->where('model_type', $user->getMorphClass())
            ->exists();
    }

    public static function ownedTeamsCountIncludingTrashed(User $user): int
    {
        return (int) $user->ownedTeams()->withTrashed()->count();
    }
}
