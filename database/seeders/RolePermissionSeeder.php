<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $case) {
            Permission::query()->updateOrCreate(
                ['name' => $case->value, 'guard_name' => 'web'],
            );
        }

        foreach (RoleName::cases() as $case) {
            /** @var Role $role */
            $role = Role::query()->updateOrCreate(
                ['name' => $case->value, 'guard_name' => 'web', 'team_id' => null],
            );

            $role->syncPermissions(
                array_map(fn (PermissionName $p) => $p->value, $case->permissions()),
            );
        }
    }
}
