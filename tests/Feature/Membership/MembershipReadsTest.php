<?php

declare(strict_types=1);

use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

function assign_role_in_team(User $user, Team $team, Role $role): void
{
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole($role->value);
}

it('teams returns every team the user holds any role in', function (): void {
    $user  = User::factory()->createOne();
    $teamA = Team::factory()->createOne(['name' => 'Team A']);
    $teamB = Team::factory()->createOne(['name' => 'Team B']);
    Team::factory()->createOne(['name' => 'Other Team']);

    assign_role_in_team($user, $teamA, Role::Manager);
    assign_role_in_team($user, $teamB, Role::Member);

    expect($user->teams()->pluck('teams.id')->all())
        ->toEqualCanonicalizing([$teamA->id, $teamB->id]);
});

it('teams returns each team only once even when the user holds multiple roles in it', function (): void {
    $user = User::factory()->createOne();
    $team = Team::factory()->createOne(['name' => 'Acme Corp']);

    assign_role_in_team($user, $team, Role::Manager);
    assign_role_in_team($user, $team, Role::Member);

    expect($user->teams()->pluck('teams.id')->all())->toEqual([$team->id]);
});

it('ownedTeams returns only teams where owner_id matches the user', function (): void {
    $user  = User::factory()->createOne();
    $owned = Team::factory()->createOne(['name' => 'Owned Team', 'owner_id' => $user->id]);
    Team::factory()->createOne(['name' => 'Member Team']); // different owner

    expect($user->ownedTeams()->pluck('id')->all())->toEqual([$owned->id]);
});

it('ownedTeams does not return teams where the user is only a member', function (): void {
    $owner = User::factory()->createOne();
    $other = User::factory()->createOne();
    $team  = Team::factory()->createOne(['name' => 'Acme Corp', 'owner_id' => $owner->id]);

    assign_role_in_team($other, $team, Role::Member);

    expect($other->ownedTeams()->pluck('id')->all())->toEqual([]);
});

it('isMemberOf returns true when the user holds any role in the team', function (): void {
    $user = User::factory()->createOne();
    $team = Team::factory()->createOne(['name' => 'Acme Corp']);

    assign_role_in_team($user, $team, Role::Member);

    expect($user->isMemberOf($team))->toBeTrue();
});

it('isMemberOf returns false when the user holds no role in the team', function (): void {
    $user = User::factory()->createOne();
    $team = Team::factory()->createOne(['name' => 'Acme Corp']);

    expect($user->isMemberOf($team))->toBeFalse();
});

it('isMemberOf returns false when the user belongs to a different team', function (): void {
    $user  = User::factory()->createOne();
    $teamA = Team::factory()->createOne(['name' => 'Team A']);
    $teamB = Team::factory()->createOne(['name' => 'Team B']);

    assign_role_in_team($user, $teamA, Role::Member);

    expect($user->isMemberOf($teamB))->toBeFalse();
});

it('members returns every user holding any role in the team', function (): void {
    $owner  = User::factory()->createOne();
    $team   = Team::factory()->createOne(['name' => 'Acme Corp', 'owner_id' => $owner->id]);
    $member = User::factory()->createOne();
    User::factory()->createOne();

    assign_role_in_team($owner, $team, Role::Manager);
    assign_role_in_team($member, $team, Role::Member);

    expect($team->members()->pluck('users.id')->all())
        ->toEqualCanonicalizing([$owner->id, $member->id]);
});

it('members returns each user only once even when they hold multiple roles in the team', function (): void {
    $team = Team::factory()->createOne(['name' => 'Acme Corp']);
    $user = User::factory()->createOne();

    assign_role_in_team($user, $team, Role::Manager);
    assign_role_in_team($user, $team, Role::Member);

    expect($team->members()->pluck('users.id')->all())->toEqual([$user->id]);
});

it('owner returns the user identified as the team owner', function (): void {
    $ownerUser = User::factory()->createOne();
    $team      = Team::factory()->createOne(['name' => 'Acme Corp', 'owner_id' => $ownerUser->id]);
    $member    = User::factory()->createOne();

    assign_role_in_team($member, $team, Role::Member);

    expect($team->owner->id)->toBe($ownerUser->id);
});
