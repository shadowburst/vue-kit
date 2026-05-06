<?php

declare(strict_types=1);

use App\Enums\Permission\Permission;
use App\Enums\Role\Role;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

use function Pest\Laravel\seed;

it('seeds all permissions and roles', function () {
    seed(RolePermissionSeeder::class);

    expect(SpatiePermission::query()->count())->toBe(count(Permission::cases()));
    expect(SpatieRole::query()->count())->toBe(count(Role::cases()));
});

it('is idempotent: running the seeder twice yields the same state', function () {
    seed(RolePermissionSeeder::class);
    seed(RolePermissionSeeder::class);

    expect(SpatiePermission::query()->count())->toBe(count(Permission::cases()));
    expect(SpatieRole::query()->count())->toBe(count(Role::cases()));

    foreach (Role::cases() as $role) {
        $spatieRole          = SpatieRole::findByName($role->value, 'web');
        $expectedPermissions = array_map(fn (Permission $p) => $p->value, $role->permissions());

        expect($spatieRole->permissions->pluck('name')->sort()->values()->all())
            ->toBe(collect($expectedPermissions)->sort()->values()->all());
    }
});

it('assigns correct permissions per role matching the enum matrix', function () {
    seed(RolePermissionSeeder::class);

    foreach (Role::cases() as $role) {
        $spatieRole          = SpatieRole::findByName($role->value, 'web');
        $expectedPermissions = array_map(fn (Permission $p) => $p->value, $role->permissions());

        expect($spatieRole->permissions->pluck('name')->sort()->values()->all())
            ->toBe(collect($expectedPermissions)->sort()->values()->all());
    }
});

it('returns the english label for each role', function () {
    app()->setLocale('en');

    expect(Role::SuperAdmin->label())->toBe('Super admin');
    expect(Role::Tester->label())->toBe('Tester');
    expect(Role::Owner->label())->toBe('Owner');
    expect(Role::Admin->label())->toBe('Admin');
    expect(Role::Member->label())->toBe('Member');
});

it('returns the french label for each role', function () {
    app()->setLocale('fr');

    expect(Role::SuperAdmin->label())->toBe('Super administrateur');
    expect(Role::Tester->label())->toBe('Testeur');
    expect(Role::Owner->label())->toBe('Propriétaire');
    expect(Role::Admin->label())->toBe('Administrateur');
    expect(Role::Member->label())->toBe('Membre');
});
