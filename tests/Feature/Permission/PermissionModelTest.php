<?php

declare(strict_types=1);

use App\Enums\Permission\Permission;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;

it('resolves the matching SpatiePermission row', function (): void {
    seed(RolePermissionSeeder::class);

    $permission = Permission::TeamView->model();

    expect($permission)->toBeInstanceOf(SpatiePermission::class)
        ->and($permission->name)->toBe('team.view');
});

it('returns the same cached instance across calls', function (): void {
    seed(RolePermissionSeeder::class);

    $first  = Permission::TeamView->model();
    $second = Permission::TeamView->model();

    expect($first)->toBe($second);
});

it('propagates PermissionDoesNotExist when the permission is not seeded', function (): void {
    expect(fn () => Permission::TeamView->model())->toThrow(PermissionDoesNotExist::class);
});

it('flushModelCache forces a fresh lookup', function (): void {
    seed(RolePermissionSeeder::class);

    $first = Permission::TeamView->model();

    Permission::flushModelCache();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $second = Permission::TeamView->model();

    expect($first)->not->toBe($second)
        ->and($second->name)->toBe('team.view');
});
