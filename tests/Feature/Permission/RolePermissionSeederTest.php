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

    expect(Role::Admin->label())->toBe('Admin');
    expect(Role::Tester->label())->toBe('Tester');
    expect(Role::Manager->label())->toBe('Manager');
    expect(Role::Member->label())->toBe('Member');
});

it('returns the french label for each role', function () {
    app()->setLocale('fr');

    expect(Role::Admin->label())->toBe('Administrateur');
    expect(Role::Tester->label())->toBe('Testeur');
    expect(Role::Manager->label())->toBe('Gestionnaire');
    expect(Role::Member->label())->toBe('Membre');
});

it('seeds the subscription.view permission', function () {
    seed(RolePermissionSeeder::class);

    expect(SpatiePermission::query()->where('name', Permission::SubscriptionView->value)->exists())->toBeTrue();
});

it('gives Admin subscription.view', function () {
    seed(RolePermissionSeeder::class);

    $admin = SpatieRole::findByName(Role::Manager->value, 'web');

    $names = $admin->permissions->pluck('name')->all();

    expect($names)->toContain(Permission::SubscriptionView->value);
});

it('gives Member no subscription permissions', function () {
    seed(RolePermissionSeeder::class);

    $member = SpatieRole::findByName(Role::Member->value, 'web');

    $names = $member->permissions->pluck('name')->all();

    expect($names)->not->toContain(Permission::SubscriptionView->value);
});

it(
    'does not seed policy-only gates as Spatie permissions (team.update, team.delete, subscription.cancel, subscription.resume)',
    function () {
        seed(RolePermissionSeeder::class);

        expect(SpatiePermission::query()->where('name', 'team.update')->exists())->toBeFalse();
        expect(SpatiePermission::query()->where('name', 'team.delete')->exists())->toBeFalse();
        expect(SpatiePermission::query()->where('name', 'subscription.cancel')->exists())->toBeFalse();
        expect(SpatiePermission::query()->where('name', 'subscription.resume')->exists())->toBeFalse();
    },
);
