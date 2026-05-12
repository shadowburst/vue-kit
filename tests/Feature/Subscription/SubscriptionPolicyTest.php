<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
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

it('allows the team owner to cancel when at 0 non-Owners', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);

    $policy = new SubscriptionPolicy;

    expect($policy->cancel($owner, $team))->toBeTrue();
});

it('blocks the team owner from cancelling when over Free cap', function (): void {
    $owner  = User::factory()->createOne();
    $team   = (new CreateTeam)->execute('Test Team', $owner);
    $member = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    config(['billing.tiers.free.member_cap' => 0]);

    $policy = new SubscriptionPolicy;

    expect($policy->cancel($owner, $team))->toBeFalse();
});

it('allows the team owner to resume regardless of member count', function (): void {
    $owner  = User::factory()->createOne();
    $team   = (new CreateTeam)->execute('Test Team', $owner);
    $member = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    $policy = new SubscriptionPolicy;

    expect($policy->resume($owner, $team))->toBeTrue();
});

it('prevents a non-owner admin from cancelling the subscription', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    $admin = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Manager->value);

    $policy = new SubscriptionPolicy;

    expect($policy->cancel($admin, $team))->toBeFalse();
});

it('prevents a non-owner admin from resuming the subscription', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    $admin = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Manager->value);

    $policy = new SubscriptionPolicy;

    expect($policy->resume($admin, $team))->toBeFalse();
});

it('prevents a member from cancelling the subscription', function (): void {
    $owner  = User::factory()->createOne();
    $team   = Team::factory()->createOne(['owner_id' => $owner->id]);
    $member = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    $policy = new SubscriptionPolicy;

    expect($policy->cancel($member, $team))->toBeFalse();
});

it('prevents a member from resuming the subscription', function (): void {
    $owner  = User::factory()->createOne();
    $team   = Team::factory()->createOne(['owner_id' => $owner->id]);
    $member = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    $policy = new SubscriptionPolicy;

    expect($policy->resume($member, $team))->toBeFalse();
});

it('allows any member with subscription.view permission to view the subscription', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);
    $admin = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Manager->value);
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
