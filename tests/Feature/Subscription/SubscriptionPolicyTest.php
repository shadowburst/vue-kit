<?php

declare(strict_types=1);

use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use App\Policies\SubscriptionPolicy;
use Illuminate\Support\Facades\Gate;
use Laravel\Cashier\Subscription;
use Spatie\Permission\PermissionRegistrar;

it('is explicitly registered in Gate for the Subscription model', function (): void {
    expect(Gate::getPolicyFor(Subscription::class))->toBeInstanceOf(SubscriptionPolicy::class);
});

it('allows the team owner to update the subscription', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);

    $policy = new SubscriptionPolicy;

    expect($policy->update($owner, $team))->toBeTrue();
});

it('prevents a non-owner admin from updating the subscription', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    $admin = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    $policy = new SubscriptionPolicy;

    expect($policy->update($admin, $team))->toBeFalse();
});

it('prevents a member from updating the subscription', function (): void {
    $owner  = User::factory()->createOne();
    $team   = Team::factory()->createOne(['owner_id' => $owner->id]);
    $member = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    $policy = new SubscriptionPolicy;

    expect($policy->update($member, $team))->toBeFalse();
});

it('allows any member with subscription.view permission to view the subscription', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    $admin = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    $policy = new SubscriptionPolicy;

    expect($policy->view($admin, $team))->toBeTrue();
});

it('prevents a member without subscription.view from viewing the subscription', function (): void {
    $owner  = User::factory()->createOne();
    $team   = Team::factory()->createOne(['owner_id' => $owner->id]);
    $member = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    $policy = new SubscriptionPolicy;

    expect($policy->view($member, $team))->toBeFalse();
});
