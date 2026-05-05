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

it('seeds the subscription.view permission', function () {
    seed(RolePermissionSeeder::class);

    expect(SpatiePermission::query()->where('name', Permission::SubscriptionView->value)->exists())->toBeTrue();
});

it('seeds the subscription.update permission', function () {
    seed(RolePermissionSeeder::class);

    expect(SpatiePermission::query()->where('name', Permission::SubscriptionUpdate->value)->exists())->toBeTrue();
});

it('gives Owner both subscription permissions', function () {
    seed(RolePermissionSeeder::class);

    $owner = SpatieRole::findByName(Role::Owner->value, 'web');

    expect($owner->permissions->pluck('name')->all())
        ->toContain(Permission::SubscriptionView->value)
        ->toContain(Permission::SubscriptionUpdate->value);
});

it('gives Admin only subscription.view', function () {
    seed(RolePermissionSeeder::class);

    $admin = SpatieRole::findByName(Role::Admin->value, 'web');

    expect($admin->permissions->pluck('name')->all())
        ->toContain(Permission::SubscriptionView->value)
        ->not->toContain(Permission::SubscriptionUpdate->value);
});

it('gives Member no subscription permissions', function () {
    seed(RolePermissionSeeder::class);

    $member = SpatieRole::findByName(Role::Member->value, 'web');

    expect($member->permissions->pluck('name')->all())
        ->not->toContain(Permission::SubscriptionView->value)
        ->not->toContain(Permission::SubscriptionUpdate->value);
});
