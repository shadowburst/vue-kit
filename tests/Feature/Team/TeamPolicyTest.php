<?php

declare(strict_types=1);

use App\Enums\Permission\PermissionName;
use App\Enums\Role\RoleName;
use App\Models\Team;
use App\Models\User;
use App\Policies\TeamPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;

// Build dataset from enum so future matrix changes propagate automatically.
$teamPermissionMap = [
    'view'   => PermissionName::TeamView,
    'update' => PermissionName::TeamUpdate,
    'delete' => PermissionName::TeamDelete,
];

$matrixDataset = [];

foreach (RoleName::cases() as $role) {
    $rolePermissions = $role->permissions();

    foreach ($teamPermissionMap as $method => $permission) {
        $matrixDataset["{$role->value}:{$method}"] = [
            $role,
            $method,
            in_array($permission, $rolePermissions, true),
        ];
    }
}

it('is auto-discovered by Laravel for the Team model', function (): void {
    expect(Gate::getPolicyFor(Team::class))->toBeInstanceOf(TeamPolicy::class);
});

it('enforces the TeamPolicy permission matrix', function (RoleName $role, string $method, bool $expected): void {
    seed(RolePermissionSeeder::class);

    $team  = Team::query()->create(['name' => 'Team One']);
    $team2 = Team::query()->create(['name' => 'Team Two']);
    $user  = User::factory()->createOne();

    // Assign the role to both teams so the ownedTeams() invariant is satisfied
    // for Owner::delete without masking the permission check for other methods.
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole($role->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team2->id);
    $user->assignRole($role->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    expect(Gate::forUser($user)->allows($method, $team))->toBe($expected);
})->with($matrixDataset);

it('prevents a sole owner from deleting their only owned team', function (): void {
    seed(RolePermissionSeeder::class);

    $team  = Team::query()->create(['name' => 'Only Team']);
    $owner = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $owner->assignRole(RoleName::Owner->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    expect(Gate::forUser($owner)->allows('delete', $team))->toBeFalse();
});

it('allows an owner of two teams to delete either of their owned teams', function (): void {
    seed(RolePermissionSeeder::class);

    $team1 = Team::query()->create(['name' => 'Team Alpha']);
    $team2 = Team::query()->create(['name' => 'Team Beta']);
    $owner = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team1->id);
    $owner->assignRole(RoleName::Owner->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team2->id);
    $owner->assignRole(RoleName::Owner->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team1->id);
    expect(Gate::forUser($owner)->allows('delete', $team1))->toBeTrue();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team2->id);
    expect(Gate::forUser($owner)->allows('delete', $team2))->toBeTrue();
});
