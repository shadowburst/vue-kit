<?php

declare(strict_types=1);

use App\Actions\Membership\AssignMembership;
use App\Enums\Permission\Permission;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\Team\TeamContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

// Build dataset from enum so future matrix changes propagate automatically.
// `create` is excluded: it is a combined permission + seat-cap check tested separately below.
// `update` is excluded: it is a combined permission + over-cap check tested separately below.
$userPermissionMap = [
    'viewAny' => Permission::UserViewAny,
    'view'    => Permission::UserView,
    'delete'  => Permission::UserDelete,
];

$allowedDataset = [];
$deniedDataset  = [];

foreach (Role::cases() as $role) {
    $rolePermissions = $role->permissions();

    foreach ($userPermissionMap as $method => $permission) {
        $key = "{$role->value}:{$method}";

        if (in_array($permission, $rolePermissions, true)) {
            $allowedDataset[$key] = [$role, $method];

            continue;
        }

        $deniedDataset[$key] = [$role, $method];
    }
}

function evaluateUserPolicy(Role $role, string $method): bool
{
    $team   = Team::factory()->createOne(['name' => 'Test Team']);
    $user   = User::factory()->createOne();
    $target = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole($role->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    return match ($method) {
        'viewAny' => Gate::forUser($user)->allows($method, User::class),
        default   => Gate::forUser($user)->allows($method, $target),
    };
}

it('is auto-discovered by Laravel for the User model', function (): void {
    expect(Gate::getPolicyFor(User::class))->toBeInstanceOf(UserPolicy::class);
});

it('grants the UserPolicy action when the role has the matching permission', function (
    Role $role,
    string $method,
): void {
    expect(evaluateUserPolicy($role, $method))->toBeTrue();
})->with($allowedDataset);

it('denies the UserPolicy action when the role lacks the matching permission', function (
    Role $role,
    string $method,
): void {
    expect(evaluateUserPolicy($role, $method))->toBeFalse();
})->with($deniedDataset);

it('allows a member to delete themselves (leave team) regardless of user.delete permission', function (): void {
    $team   = Team::factory()->createOne(['name' => 'Test Team']);
    $member = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    expect($member->can(Permission::UserDelete->value))->toBeFalse();
    expect(Gate::forUser($member)->allows('delete', $member))->toBeTrue();
});

it('does not grant UserPolicy::update to an admin via a before() bypass', function (): void {
    $team   = Team::factory()->createOne(['name' => 'Test Team']);
    $admin  = User::factory()->createOne();
    $target = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    // Admin only holds 'admin' (the permission); no user.update — no before() bypass exists.
    expect(Gate::forUser($admin)->allows('update', [$target, $team]))->toBeFalse();
});

// ─── UserPolicy::create — seat-cap matrix ──────────────────────────────────
//
// Dimensions: Tier (Free|Pro) × cap state (under|at|over) × role permission
// (has user.create | lacks user.create)
//
// Free tier: cap = 0 → "at-cap" is the only reachable state with 0 non-owner
// members; "over-cap" requires pre-existing members (involuntary path).
// Pro tier: cap = 3 → under (<3), at (=3), over (>3).

function insertProSubscription(Team $team): void
{
    DB::table('subscriptions')->insert([
        'team_id'       => $team->id,
        'type'          => 'default',
        'stripe_id'     => 'sub_cap_'.$team->id,
        'stripe_status' => 'active',
        'stripe_price'  => config('billing.tiers.pro.monthly'),
        'created_at'    => now()->toDateTimeString(),
        'updated_at'    => now()->toDateTimeString(),
    ]);
}

function pushNonOwnerMember(Team $team): User
{
    $member = User::factory()->createOne();
    (new AssignMembership)->execute($member, $team, Role::Member);

    return $member;
}

function assertCreateAllowed(User $user, Team $team): void
{
    app(TeamContext::class)->setTeam($team);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    expect(Gate::forUser($user)->allows('create', User::class))->toBeTrue();
}

function assertCreateDenied(User $user, Team $team): void
{
    app(TeamContext::class)->setTeam($team);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    expect(Gate::forUser($user)->allows('create', User::class))->toBeFalse();
}

// ── Free tier ────────────────────────────────────────────────────────────────

it('UserPolicy::create: Free/at-cap/Owner → false (has permission, cap=0)', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    assignRoleInTeam($owner, $team, Role::Manager);

    assertCreateDenied($owner, $team);
});

it('UserPolicy::create: Free/at-cap/Admin → false (has permission, cap=0)', function (): void {
    $team  = Team::factory()->createOne();
    $admin = User::factory()->createOne();
    assignRoleInTeam($admin, $team, Role::Manager);

    assertCreateDenied($admin, $team);
});

it('UserPolicy::create: Free/at-cap/Member → false (lacks permission, cap=0)', function (): void {
    $team   = Team::factory()->createOne();
    $member = User::factory()->createOne();
    assignRoleInTeam($member, $team, Role::Member);

    assertCreateDenied($member, $team);
});

it('UserPolicy::create: Free/over-cap/Owner → false (1 non-owner member present)', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    assignRoleInTeam($owner, $team, Role::Manager);
    pushNonOwnerMember($team);

    assertCreateDenied($owner, $team);
});

// ── Pro tier ─────────────────────────────────────────────────────────────────

it('UserPolicy::create: Pro/under-cap/Owner → true (2 of 3 seats used)', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    insertProSubscription($team);
    assignRoleInTeam($owner, $team, Role::Manager);
    pushNonOwnerMember($team);
    pushNonOwnerMember($team);

    assertCreateAllowed($owner, $team);
});

it('UserPolicy::create: Pro/under-cap/Admin → true (1 of 3 seats used)', function (): void {
    $team  = Team::factory()->createOne();
    $admin = User::factory()->createOne();
    insertProSubscription($team);
    assignRoleInTeam($admin, $team, Role::Manager);
    pushNonOwnerMember($team);

    assertCreateAllowed($admin, $team);
});

it('UserPolicy::create: Pro/under-cap/Member → false (lacks permission)', function (): void {
    $team   = Team::factory()->createOne();
    $member = User::factory()->createOne();
    insertProSubscription($team);
    assignRoleInTeam($member, $team, Role::Member);

    assertCreateDenied($member, $team);
});

it('UserPolicy::create: Pro/at-cap/Owner → false (3 of 3 seats used)', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    insertProSubscription($team);
    assignRoleInTeam($owner, $team, Role::Manager);
    pushNonOwnerMember($team);
    pushNonOwnerMember($team);
    pushNonOwnerMember($team);

    assertCreateDenied($owner, $team);
});

it('UserPolicy::create: Pro/over-cap/Owner → false (involuntary-cancellation path; 4 members)', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    insertProSubscription($team);
    assignRoleInTeam($owner, $team, Role::Manager);
    pushNonOwnerMember($team);
    pushNonOwnerMember($team);
    pushNonOwnerMember($team);
    pushNonOwnerMember($team);

    assertCreateDenied($owner, $team);
});

// ─── UserPolicy::update — combined permission + over-cap ─────────────────────
//
// Over-cap blocks update even for roles with user.update permission.
// UserPolicy::delete is NOT gated by isOverCap (escape hatch for member removal).

it('UserPolicy::update: Manager under-cap → true (has permission, under Free cap with Pro sub)', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    insertProSubscription($team);
    assignRoleInTeam($owner, $team, Role::Manager);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    $target = User::factory()->createOne();

    expect(Gate::forUser($owner)->allows('update', [$target, $team]))->toBeTrue();
});

it('UserPolicy::update: Manager over-cap → false (Free cap=0, 1 non-owner member present)', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    assignRoleInTeam($owner, $team, Role::Manager);
    pushNonOwnerMember($team);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    $target = User::factory()->createOne();

    expect(Gate::forUser($owner)->allows('update', [$target, $team]))->toBeFalse();
});

it('UserPolicy::delete: allowed even when team is over-cap (escape hatch)', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    assignRoleInTeam($owner, $team, Role::Manager);
    $member = pushNonOwnerMember($team);

    app(TeamContext::class)->setTeam($team);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    // Over-cap (1 non-owner vs Free cap=0) must NOT block delete.
    expect(Gate::forUser($owner)->allows('delete', $member))->toBeTrue();
});
