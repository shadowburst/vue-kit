<?php

declare(strict_types=1);

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Team;
use App\Models\User;
use App\Policies\UserPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;

// Build dataset from enum so future matrix changes propagate automatically.
$userPermissionMap = [
    'viewAny' => PermissionName::UserViewAny,
    'view'    => PermissionName::UserView,
    'create'  => PermissionName::UserCreate,
    'update'  => PermissionName::UserUpdate,
    'delete'  => PermissionName::UserDelete,
];

$matrixDataset = [];

foreach (RoleName::cases() as $role) {
    $rolePermissions = $role->permissions();

    foreach ($userPermissionMap as $method => $permission) {
        $matrixDataset["{$role->value}:{$method}"] = [
            $role,
            $method,
            in_array($permission, $rolePermissions, true),
        ];
    }
}

it('is auto-discovered by Laravel for the User model', function (): void {
    expect(Gate::getPolicyFor(User::class))->toBeInstanceOf(UserPolicy::class);
});

it('enforces the UserPolicy permission matrix', function (RoleName $role, string $method, bool $expected): void {
    seed(RolePermissionSeeder::class);

    $team   = Team::query()->create(['name' => 'Test Team']);
    $user   = User::factory()->createOne();
    $target = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole($role->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    $result = match ($method) {
        'viewAny', 'create' => Gate::forUser($user)->allows($method, User::class),
        default             => Gate::forUser($user)->allows($method, $target),
    };

    expect($result)->toBe($expected);
})->with($matrixDataset);

it('allows a member to delete themselves (leave team) regardless of user.delete permission', function (): void {
    seed(RolePermissionSeeder::class);

    $team   = Team::query()->create(['name' => 'Test Team']);
    $member = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(RoleName::Member->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    expect($member->can(PermissionName::UserDelete->value))->toBeFalse();
    expect(Gate::forUser($member)->allows('delete', $member))->toBeTrue();
});

it('does not grant UserPolicy::update to a super-admin via a before() bypass', function (): void {
    seed(RolePermissionSeeder::class);

    $team       = Team::query()->create(['name' => 'Test Team']);
    $superAdmin = User::factory()->createOne();
    $target     = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    // SuperAdmin only holds 'admin'; no user.update — no before() bypass exists.
    expect(Gate::forUser($superAdmin)->allows('update', $target))->toBeFalse();
});
