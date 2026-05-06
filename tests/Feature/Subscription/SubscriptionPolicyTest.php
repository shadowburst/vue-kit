<?php

declare(strict_types=1);

use App\Enums\Permission\Permission;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use App\Policies\SubscriptionPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Gate;
use Laravel\Cashier\Subscription;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;

// Build dataset from enum so future matrix changes propagate automatically.
$subscriptionPermissionMap = [
    'view'   => Permission::SubscriptionView,
    'update' => Permission::SubscriptionUpdate,
];

$matrixDataset = [];

foreach (Role::cases() as $role) {
    $rolePermissions = $role->permissions();

    foreach ($subscriptionPermissionMap as $method => $permission) {
        $matrixDataset["{$role->value}:{$method}"] = [
            $role,
            $method,
            in_array($permission, $rolePermissions, true),
        ];
    }
}

it('is explicitly registered in Gate for the Subscription model', function (): void {
    expect(Gate::getPolicyFor(Subscription::class))->toBeInstanceOf(SubscriptionPolicy::class);
});

it('enforces the SubscriptionPolicy permission matrix', function (Role $role, string $method, bool $expected): void {
    seed(RolePermissionSeeder::class);

    $team = Team::query()->create(['name' => 'Test Team']);
    $user = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole($role->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    $policy = new SubscriptionPolicy;

    expect($policy->{$method}($user, $team))->toBe($expected);
})->with($matrixDataset);
