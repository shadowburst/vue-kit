<?php

declare(strict_types=1);

use App\Actions\Membership\AssignMembership;
use App\Actions\Membership\ChangeMembershipRole;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

it('changes a Member role to Admin within the team', function (): void {
    $user = User::factory()->createOne();
    $team = Team::factory()->createOne(['name' => 'Acme Corp']);

    (new AssignMembership)->execute($user, $team, Role::Member);
    (new ChangeMembershipRole)->execute($user, $team, Role::Admin);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->refresh();

    expect($user->hasRole(Role::Admin->value))->toBeTrue();
    expect($user->hasRole(Role::Member->value))->toBeFalse();
});

it('is a no-op when the user already holds the target role', function (): void {
    $user = User::factory()->createOne();
    $team = Team::factory()->createOne(['name' => 'Acme Corp']);

    (new AssignMembership)->execute($user, $team, Role::Member);
    (new ChangeMembershipRole)->execute($user, $team, Role::Member);

    $roleCount = DB::table('model_has_roles')
        ->where('model_id', $user->id)
        ->where('model_type', $user->getMorphClass())
        ->where('team_id', $team->id)
        ->count();

    expect($roleCount)->toBe(1);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    expect($user->refresh()->hasRole(Role::Member->value))->toBeTrue();
});

it('does not affect the same user\'s role in other teams', function (): void {
    $user  = User::factory()->createOne();
    $teamA = Team::factory()->createOne(['name' => 'Team A']);
    $teamB = Team::factory()->createOne(['name' => 'Team B']);

    (new AssignMembership)->execute($user, $teamA, Role::Member);
    (new AssignMembership)->execute($user, $teamB, Role::Member);

    (new ChangeMembershipRole)->execute($user, $teamA, Role::Admin);

    app(PermissionRegistrar::class)->setPermissionsTeamId($teamA->id);
    $user->refresh();

    expect($user->hasRole(Role::Admin->value))->toBeTrue();
    expect($user->hasRole(Role::Member->value))->toBeFalse();

    app(PermissionRegistrar::class)->setPermissionsTeamId($teamB->id);
    $user->refresh();

    expect($user->hasRole(Role::Member->value))->toBeTrue();
});
