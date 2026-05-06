<?php

declare(strict_types=1);

use App\Enums\Permission\Permission;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use App\Policies\TeamPolicy;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

it('is auto-discovered by Laravel for the Team model', function (): void {
    expect(Gate::getPolicyFor(Team::class))->toBeInstanceOf(TeamPolicy::class);
});

// team.view is still permission-based (Permission::TeamView held by Member, not Admin)
it('allows a member to view a team', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    $user  = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole(Role::Member->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    expect(Gate::forUser($user)->allows('view', $team))->toBeTrue();
});

it('prevents an admin from viewing a team (no Permission::TeamView)', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    $user  = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole(Role::Admin->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    expect(Gate::forUser($user)->allows('view', $team))->toBeFalse();
});

// team.update and team.delete are identity-based (owner_id === user.id)
it('allows the owner to update their team', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);

    expect(Gate::forUser($owner)->allows('update', $team))->toBeTrue();
});

it('prevents a non-owner admin from updating the team', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    $admin = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    expect(Gate::forUser($admin)->allows('update', $team))->toBeFalse();
});

it('prevents a member from updating the team', function (): void {
    $owner  = User::factory()->createOne();
    $team   = Team::factory()->createOne(['owner_id' => $owner->id]);
    $member = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    expect(Gate::forUser($member)->allows('update', $team))->toBeFalse();
});

it('allows the owner of multiple teams to delete either of their owned teams', function (): void {
    $owner = User::factory()->createOne();
    $team1 = Team::factory()->createOne(['owner_id' => $owner->id]);
    $team2 = Team::factory()->createOne(['owner_id' => $owner->id]);

    expect(Gate::forUser($owner)->allows('delete', $team1))->toBeTrue();
    expect(Gate::forUser($owner)->allows('delete', $team2))->toBeTrue();
});

it('prevents a sole owner from deleting their only owned team', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);

    expect(Gate::forUser($owner)->allows('delete', $team))->toBeFalse();
});

it('prevents a non-owner admin from deleting the team', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    $admin = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    expect(Gate::forUser($admin)->allows('delete', $team))->toBeFalse();
});
