<?php

declare(strict_types=1);

use App\Data\Auth\AuthAbilitiesData;
use App\Enums\Permission\Permission;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use App\Services\Team\TeamContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;

// Dataset drives permission-based keys only (user.*, team.view, subscription.view).
// Identity-based keys (team.update, team.delete, subscription.cancel, subscription.resume) are tested separately.
$dataset = [];

foreach (Role::cases() as $role) {
    $permissions = $role->permissions();

    $dataset[$role->value] = [
        $role,
        [
            'view_any' => in_array(Permission::UserViewAny, $permissions, true),
            'view'     => in_array(Permission::UserView, $permissions, true),
            // `create` reflects the combined permission + seat-cap gate:
            // resolved against a Pro under-cap team in the test body.
            'create' => in_array(Permission::UserCreate, $permissions, true),
            'update' => in_array(Permission::UserUpdate, $permissions, true),
            'delete' => in_array(Permission::UserDelete, $permissions, true),
        ],
        [
            'view' => in_array(Permission::TeamView, $permissions, true),
        ],
        [
            'view' => in_array(Permission::SubscriptionView, $permissions, true),
        ],
    ];
}

it('returns correct permission-based booleans for each role', function (
    Role $role,
    array $expectedUser,
    array $expectedTeamView,
    array $expectedSubscriptionView,
): void {
    seed(RolePermissionSeeder::class);

    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    $user  = User::factory()->createOne();

    // Pro subscription with cap=3 ensures the seat-cap gate doesn't poison the
    // `create` expectation: only role-permission drives it for under-cap teams.
    DB::table('subscriptions')->insert([
        'team_id'       => $team->id,
        'type'          => 'default',
        'stripe_id'     => 'sub_abilities_test_'.$team->id,
        'stripe_status' => 'active',
        'stripe_price'  => config('billing.tiers.pro.monthly'),
        'created_at'    => now()->toDateTimeString(),
        'updated_at'    => now()->toDateTimeString(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole($role->value);
    app(TeamContext::class)->setTeam($team);

    $abilities = AuthAbilitiesData::fromUser($user, $team);

    expect($abilities->user)->toBe($expectedUser);
    expect($abilities->team['view'])->toBe($expectedTeamView['view']);
    expect($abilities->subscription['view'])->toBe($expectedSubscriptionView['view']);
})->with($dataset);

it('returns true for team.update, subscription.cancel, and subscription.resume when user is the team owner at cap', function (): void {
    seed(RolePermissionSeeder::class);

    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $owner->assignRole(Role::Manager->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    $abilities = AuthAbilitiesData::fromUser($owner, $team);

    expect($abilities->team['update'])->toBeTrue();
    expect($abilities->subscription['cancel'])->toBeTrue();
    expect($abilities->subscription['resume'])->toBeTrue();
});

it('returns false for team.delete when the owner has only one owned team', function (): void {
    seed(RolePermissionSeeder::class);

    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $owner->assignRole(Role::Manager->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    $abilities = AuthAbilitiesData::fromUser($owner, $team);

    expect($abilities->team['delete'])->toBeFalse();
});

it('returns true for team.delete when the owner has multiple owned teams', function (): void {
    seed(RolePermissionSeeder::class);

    $owner = User::factory()->createOne();
    $team1 = Team::factory()->createOne(['owner_id' => $owner->id]);
    Team::factory()->createOne(['owner_id' => $owner->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team1->id);
    $owner->assignRole(Role::Manager->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team1->id);

    $abilities = AuthAbilitiesData::fromUser($owner, $team1);

    expect($abilities->team['delete'])->toBeTrue();
});

it('returns false for identity-based keys when user is not the owner', function (): void {
    seed(RolePermissionSeeder::class);

    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    $admin = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Manager->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    $abilities = AuthAbilitiesData::fromUser($admin, $team);

    expect($abilities->team['update'])->toBeFalse();
    expect($abilities->team['delete'])->toBeFalse();
    expect($abilities->subscription['cancel'])->toBeFalse();
    expect($abilities->subscription['resume'])->toBeFalse();
});

it('returns all-false abilities for a guest (null user)', function (): void {
    seed(RolePermissionSeeder::class);

    $abilities = AuthAbilitiesData::fromUser();

    expect($abilities)
        ->toBeInstanceOf(AuthAbilitiesData::class)
        ->and($abilities->user)
        ->toBe([
            'view_any' => false,
            'view'     => false,
            'create'   => false,
            'update'   => false,
            'delete'   => false,
        ])
        ->and($abilities->team)
        ->toBe([
            'view'   => false,
            'update' => false,
            'delete' => false,
        ])
        ->and($abilities->subscription)
        ->toBe([
            'view'   => false,
            'create' => false,
            'update' => false,
            'cancel' => false,
            'resume' => false,
        ]);
});
