<?php

declare(strict_types=1);

use App\Enums\Role\Role;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

it('resolves the matching SpatieRole row', function (): void {
    $role = Role::Admin->model();

    expect($role)->toBeInstanceOf(SpatieRole::class)->and($role->name)->toBe('admin');
});

it('returns the same cached instance across calls in the same team context', function (): void {
    $first  = Role::Admin->model();
    $second = Role::Admin->model();

    expect($first)->toBe($second);
});

it('keys the cache by team context', function (): void {
    SpatieRole::query()->delete();
    SpatieRole::query()->create(['name' => 'admin', 'guard_name' => 'web', 'team_id' => 1]);
    SpatieRole::query()->create(['name' => 'admin', 'guard_name' => 'web', 'team_id' => 2]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(1);
    $teamOneRole = Role::Admin->model();

    app(PermissionRegistrar::class)->setPermissionsTeamId(2);
    $teamTwoRole = Role::Admin->model();

    expect($teamOneRole->team_id)->toBe(1)->and($teamTwoRole->team_id)->toBe(2);
});

it('propagates RoleDoesNotExist when the role is not seeded', function (): void {
    SpatieRole::query()->delete();

    expect(fn () => Role::Admin->model())->toThrow(RoleDoesNotExist::class);
});

it('flushModelCache forces a fresh lookup', function (): void {
    $first = Role::Admin->model();

    Role::flushModelCache();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $second = Role::Admin->model();

    expect($first)->not->toBe($second);
    expect($second->name)->toBe('admin');
});
