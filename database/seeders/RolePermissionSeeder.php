<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Permission\Permission;
use App\Enums\Role\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $case) {
            SpatiePermission::query()->updateOrCreate(
                ['name' => $case->value, 'guard_name' => 'web'],
            );
        }

        foreach (Role::cases() as $case) {
            /** @var SpatieRole $role */
            $role = SpatieRole::query()->updateOrCreate(
                ['name' => $case->value, 'guard_name' => 'web', 'team_id' => null],
            );

            $role->syncPermissions(
                array_map(fn (Permission $p) => $p->value, $case->permissions()),
            );
        }
    }
}
