<?php

declare(strict_types=1);

use App\Actions\Membership\AssignMembership;
use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;

function overcapCreateTeam(): Team
{
    $owner = User::factory()->createOne();

    return (new CreateTeam)->execute('Acme', $owner);
}

function overcapAddMember(Team $team): User
{
    $member = User::factory()->createOne();
    (new AssignMembership)->execute($member, $team, Role::Member);

    return $member;
}

// ─── Free tier (cap = 0) ─────────────────────────────────────────────────────

test('isOverCap returns false when team has no non-owner members (at cap = 0)', function (): void {
    $team = overcapCreateTeam();

    expect($team->isOverCap())->toBeFalse();
});

test('isOverCap returns true when team has one non-owner member on Free tier (cap = 0)', function (): void {
    $team = overcapCreateTeam();
    overcapAddMember($team);

    expect($team->isOverCap())->toBeTrue();
});

// ─── Pro tier (cap = 3) ──────────────────────────────────────────────────────

test('isOverCap returns false when team is under Pro cap (2 of 3 seats)', function (): void {
    $team = overcapCreateTeam();

    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_overcap_test_under',
        'stripe_status' => 'active',
        'stripe_price'  => config('billing.tiers.pro.monthly'),
    ]);

    overcapAddMember($team);
    overcapAddMember($team);

    expect($team->isOverCap())->toBeFalse();
});

test('isOverCap returns false when team is exactly at Pro cap (3 of 3 seats)', function (): void {
    $team = overcapCreateTeam();

    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_overcap_test_at',
        'stripe_status' => 'active',
        'stripe_price'  => config('billing.tiers.pro.monthly'),
    ]);

    overcapAddMember($team);
    overcapAddMember($team);
    overcapAddMember($team);

    expect($team->isOverCap())->toBeFalse();
});

test('isOverCap returns true when team exceeds Pro cap (4 of 3 seats)', function (): void {
    $team = overcapCreateTeam();

    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_overcap_test_over',
        'stripe_status' => 'active',
        'stripe_price'  => config('billing.tiers.pro.monthly'),
    ]);

    overcapAddMember($team);
    overcapAddMember($team);
    overcapAddMember($team);
    overcapAddMember($team);

    expect($team->isOverCap())->toBeTrue();
});
