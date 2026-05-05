<?php

declare(strict_types=1);

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('seeds all permissions and roles', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Permission::count())->toBe(count(PermissionName::cases()));
    expect(Role::count())->toBe(count(RoleName::cases()));
});

it('is idempotent: running the seeder twice yields the same state', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    expect(Permission::count())->toBe(count(PermissionName::cases()));
    expect(Role::count())->toBe(count(RoleName::cases()));

    foreach (RoleName::cases() as $roleName) {
        $role                = Role::findByName($roleName->value, 'web');
        $expectedPermissions = array_map(fn (PermissionName $p) => $p->value, $roleName->permissions());

        expect($role->permissions->pluck('name')->sort()->values()->all())
            ->toBe(collect($expectedPermissions)->sort()->values()->all());
    }
});

it('assigns correct permissions per role matching the enum matrix', function () {
    $this->seed(RolePermissionSeeder::class);

    foreach (RoleName::cases() as $roleName) {
        $role                = Role::findByName($roleName->value, 'web');
        $expectedPermissions = array_map(fn (PermissionName $p) => $p->value, $roleName->permissions());

        expect($role->permissions->pluck('name')->sort()->values()->all())
            ->toBe(collect($expectedPermissions)->sort()->values()->all());
    }
});

it('returns the english label for each role', function () {
    app()->setLocale('en');

    expect(RoleName::SuperAdmin->label())->toBe('Super admin');
    expect(RoleName::Tester->label())->toBe('Tester');
    expect(RoleName::Owner->label())->toBe('Owner');
    expect(RoleName::Admin->label())->toBe('Admin');
    expect(RoleName::Member->label())->toBe('Member');
});

it('returns the french label for each role', function () {
    app()->setLocale('fr');

    expect(RoleName::SuperAdmin->label())->toBe('Super administrateur');
    expect(RoleName::Tester->label())->toBe('Testeur');
    expect(RoleName::Owner->label())->toBe('Propriétaire');
    expect(RoleName::Admin->label())->toBe('Administrateur');
    expect(RoleName::Member->label())->toBe('Membre');
});
