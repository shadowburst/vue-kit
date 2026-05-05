<?php

declare(strict_types=1);

use App\Actions\Membership\AssignMembership;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('assigns a Member role to a user scoped to the team', function (): void {
    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    (new AssignMembership)->execute($user, $team, Role::Member);

    $hasMemberRole = DB::table('model_has_roles')
        ->where('model_has_roles.model_id', $user->id)
        ->where('model_has_roles.model_type', $user->getMorphClass())
        ->where('model_has_roles.team_id', $team->id)
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', Role::Member->value)
        ->exists();

    expect($hasMemberRole)->toBeTrue();
});

it('assigns an Owner role to a user scoped to the team', function (): void {
    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    (new AssignMembership)->execute($user, $team, Role::Owner);

    $hasOwnerRole = DB::table('model_has_roles')
        ->where('model_has_roles.model_id', $user->id)
        ->where('model_has_roles.model_type', $user->getMorphClass())
        ->where('model_has_roles.team_id', $team->id)
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', Role::Owner->value)
        ->exists();

    expect($hasOwnerRole)->toBeTrue();
});

it('is idempotent when the same role is assigned twice', function (): void {
    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    (new AssignMembership)->execute($user, $team, Role::Member);
    (new AssignMembership)->execute($user, $team, Role::Member);

    $roleCount = DB::table('model_has_roles')
        ->where('model_id', $user->id)
        ->where('model_type', $user->getMorphClass())
        ->count();

    expect($roleCount)->toBe(1);
});
