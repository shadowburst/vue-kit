<?php

declare(strict_types=1);

use App\Enums\Role\Role;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;

it('resolves the matching SpatieRole row', function (): void {
    seed(RolePermissionSeeder::class);

    $role = Role::Owner->model();

    expect($role)->toBeInstanceOf(SpatieRole::class)
        ->and($role->name)->toBe('owner');
});

it('returns the same cached instance across calls in the same team context', function (): void {
    seed(RolePermissionSeeder::class);

    $first  = Role::Owner->model();
    $second = Role::Owner->model();

    expect($first)->toBe($second);
});

it('keys the cache by team context', function (): void {
    SpatieRole::query()->create(['name' => 'owner', 'guard_name' => 'web', 'team_id' => 1]);
    SpatieRole::query()->create(['name' => 'owner', 'guard_name' => 'web', 'team_id' => 2]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(1);
    $teamOneRole = Role::Owner->model();

    app(PermissionRegistrar::class)->setPermissionsTeamId(2);
    $teamTwoRole = Role::Owner->model();

    expect($teamOneRole->team_id)->toBe(1)
        ->and($teamTwoRole->team_id)->toBe(2);
});

it('propagates RoleDoesNotExist when the role is not seeded', function (): void {
    expect(fn () => Role::Owner->model())->toThrow(RoleDoesNotExist::class);
});

it('flushModelCache forces a fresh lookup', function (): void {
    seed(RolePermissionSeeder::class);

    $first = Role::Owner->model();

    Role::flushModelCache();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $second = Role::Owner->model();

    expect($first)->not->toBe($second)
        ->and($second->name)->toBe('owner');
});
