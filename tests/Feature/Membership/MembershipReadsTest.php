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
    $teamA = Team::query()->create(['name' => 'Team A']);
    $teamB = Team::query()->create(['name' => 'Team B']);
    Team::query()->create(['name' => 'Other Team']);

    assign_role_in_team($user, $teamA, Role::Owner);
    assign_role_in_team($user, $teamB, Role::Member);

    expect($user->teams()->pluck('teams.id')->all())
        ->toEqualCanonicalizing([$teamA->id, $teamB->id]);
});

it('teams returns each team only once even when the user holds multiple roles in it', function (): void {
    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    assign_role_in_team($user, $team, Role::Owner);
    assign_role_in_team($user, $team, Role::Admin);

    expect($user->teams()->pluck('teams.id')->all())->toEqual([$team->id]);
});

it('ownedTeams returns only teams where the user holds the Owner role', function (): void {
    $user   = User::factory()->createOne();
    $owned  = Team::query()->create(['name' => 'Owned Team']);
    $member = Team::query()->create(['name' => 'Member Team']);

    assign_role_in_team($user, $owned, Role::Owner);
    assign_role_in_team($user, $member, Role::Member);

    expect($user->ownedTeams()->pluck('teams.id')->all())->toEqual([$owned->id]);
});

it('isMemberOf returns true when the user holds any role in the team', function (): void {
    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    assign_role_in_team($user, $team, Role::Member);

    expect($user->isMemberOf($team))->toBeTrue();
});

it('isMemberOf returns false when the user holds no role in the team', function (): void {
    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    expect($user->isMemberOf($team))->toBeFalse();
});

it('isMemberOf returns false when the user belongs to a different team', function (): void {
    $user  = User::factory()->createOne();
    $teamA = Team::query()->create(['name' => 'Team A']);
    $teamB = Team::query()->create(['name' => 'Team B']);

    assign_role_in_team($user, $teamA, Role::Member);

    expect($user->isMemberOf($teamB))->toBeFalse();
});

it('members returns every user holding any role in the team', function (): void {
    $team   = Team::query()->create(['name' => 'Acme Corp']);
    $owner  = User::factory()->createOne();
    $member = User::factory()->createOne();
    User::factory()->createOne();

    assign_role_in_team($owner, $team, Role::Owner);
    assign_role_in_team($member, $team, Role::Member);

    expect($team->members()->pluck('users.id')->all())
        ->toEqualCanonicalizing([$owner->id, $member->id]);
});

it('members returns each user only once even when they hold multiple roles in the team', function (): void {
    $team = Team::query()->create(['name' => 'Acme Corp']);
    $user = User::factory()->createOne();

    assign_role_in_team($user, $team, Role::Owner);
    assign_role_in_team($user, $team, Role::Admin);

    expect($team->members()->pluck('users.id')->all())->toEqual([$user->id]);
});

it('owners returns only users holding the Owner role in the team', function (): void {
    $team   = Team::query()->create(['name' => 'Acme Corp']);
    $owner  = User::factory()->createOne();
    $member = User::factory()->createOne();

    assign_role_in_team($owner, $team, Role::Owner);
    assign_role_in_team($member, $team, Role::Member);

    expect($team->owners()->pluck('users.id')->all())->toEqual([$owner->id]);
});
