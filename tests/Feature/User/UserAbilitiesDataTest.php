<?php

declare(strict_types=1);

use App\Data\User\UserAbilitiesData;
use App\Enums\Permission\Permission;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;

$dataset = [];

foreach (Role::cases() as $role) {
    $permissions = $role->permissions();

    $dataset[$role->value] = [
        $role,
        [
            'viewAny' => in_array(Permission::UserViewAny, $permissions, true),
            'view'    => in_array(Permission::UserView, $permissions, true),
            'create'  => in_array(Permission::UserCreate, $permissions, true),
            'update'  => in_array(Permission::UserUpdate, $permissions, true),
            'delete'  => in_array(Permission::UserDelete, $permissions, true),
        ],
        [
            'view'   => in_array(Permission::TeamView, $permissions, true),
            'update' => in_array(Permission::TeamUpdate, $permissions, true),
            'delete' => in_array(Permission::TeamDelete, $permissions, true),
        ],
        [
            'view'   => in_array(Permission::SubscriptionView, $permissions, true),
            'update' => in_array(Permission::SubscriptionUpdate, $permissions, true),
        ],
    ];
}

it('UserAbilitiesData::fromUser returns correct per-policy booleans for each role', function (
    Role $role,
    array $expectedUser,
    array $expectedTeam,
    array $expectedSubscription,
): void {
    seed(RolePermissionSeeder::class);

    $team = Team::query()->create(['name' => 'Test Team']);
    $user = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole($role->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    $abilities = UserAbilitiesData::fromUser($user);

    expect($abilities)->toBeInstanceOf(UserAbilitiesData::class)
        ->and($abilities->user)->toBe($expectedUser)
        ->and($abilities->team)->toBe($expectedTeam)
        ->and($abilities->subscription)->toBe($expectedSubscription);
})->with($dataset);
