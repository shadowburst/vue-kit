<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;

it('isMemberOf returns true when the user has any role in the team', function (): void {
    seed(RolePermissionSeeder::class);

    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole(RoleName::Member->value);

    expect($user->isMemberOf($team))->toBeTrue();
});

it('isMemberOf returns false when the user has no role in the team', function (): void {
    seed(RolePermissionSeeder::class);

    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    expect($user->isMemberOf($team))->toBeFalse();
});

it('isMemberOf returns false when the user belongs to a different team', function (): void {
    seed(RolePermissionSeeder::class);

    $user  = User::factory()->createOne();
    $teamA = Team::query()->create(['name' => 'Team A']);
    $teamB = Team::query()->create(['name' => 'Team B']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($teamA->id);
    $user->assignRole(RoleName::Member->value);

    expect($user->isMemberOf($teamB))->toBeFalse();
});

it('isOwnerOf returns true when the user holds the Owner role in the team', function (): void {
    seed(RolePermissionSeeder::class);

    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole(RoleName::Owner->value);

    expect($user->isOwnerOf($team))->toBeTrue();
});

it('isOwnerOf returns false when the user is a non-owner member', function (): void {
    seed(RolePermissionSeeder::class);

    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole(RoleName::Member->value);

    expect($user->isOwnerOf($team))->toBeFalse();
});

it('isOwnerOf returns false when the user has no role in the team', function (): void {
    seed(RolePermissionSeeder::class);

    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    expect($user->isOwnerOf($team))->toBeFalse();
});

it('roleIn returns the matching RoleName when the user has a team-scoped role', function (): void {
    seed(RolePermissionSeeder::class);

    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole(RoleName::Admin->value);

    expect($user->roleIn($team))->toBe(RoleName::Admin);
});

it('roleIn returns Owner when the user owns the team', function (): void {
    seed(RolePermissionSeeder::class);

    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole(RoleName::Owner->value);

    expect($user->roleIn($team))->toBe(RoleName::Owner);
});

it('roleIn returns null when the user has no role in the team', function (): void {
    seed(RolePermissionSeeder::class);

    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    expect($user->roleIn($team))->toBeNull();
});

it('roleIn returns null when the user belongs to a different team', function (): void {
    seed(RolePermissionSeeder::class);

    $user  = User::factory()->createOne();
    $teamA = Team::query()->create(['name' => 'Team A']);
    $teamB = Team::query()->create(['name' => 'Team B']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($teamA->id);
    $user->assignRole(RoleName::Member->value);

    expect($user->roleIn($teamB))->toBeNull();
});
