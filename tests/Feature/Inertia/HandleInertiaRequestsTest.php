<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Permission\Permission;
use App\Enums\Role\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guest request shares null currentTeam and null auth.user', function (): void {
    get(route('login'))
        ->assertOk()
        /** @mago-expect analysis:non-documented-method */
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('currentTeam', null)
                ->where('auth.user', null)
                ->where('auth.abilities', null),
        );
});

// The remaining tests pre-bind app('currentTeam') and setPermissionsTeamId directly
// because teams.create (the only auth-only Inertia route) is exempt from SetCurrentTeam
// to prevent redirect loops — SetCurrentTeam's own test covers that flow.

test('authenticated Owner gets correct currentTeam, teams, and permissions', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Acme Corp', $user);
    $user->update(['current_team_id' => $team->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app()->instance('currentTeam', $team);

    actingAs($user);

    $expectedPermissions = collect(Role::Owner->permissions())
        ->map(fn (Permission $p) => $p->value)
        ->sort()
        ->values()
        ->all();

    get(route('dashboard'))
        ->assertOk()
        /** @mago-expect analysis:non-documented-method */
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('currentTeam.id', $team->id)
                ->where('currentTeam.name', 'Acme Corp')
                ->where('currentTeam.slug', 'acme-corp')
                ->has('auth.user.teams', 1)
                ->where('auth.user.teams.0.id', $team->id)
                ->where('auth.user.teams.0.name', 'Acme Corp')
                ->where('auth.user.permissions', $expectedPermissions)
                ->where('auth.abilities.user.viewAny', true)
                ->where('auth.abilities.user.view', true)
                ->where('auth.abilities.user.create', true)
                ->where('auth.abilities.user.update', true)
                ->where('auth.abilities.user.delete', true)
                ->where('auth.abilities.team.view', true)
                ->where('auth.abilities.team.update', true)
                ->where('auth.abilities.team.delete', true)
                ->where('auth.abilities.subscription.view', true)
                ->where('auth.abilities.subscription.update', true),
        );
});

test('authenticated Admin gets correct currentTeam, teams, and permissions', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme Corp', $owner);

    $admin = User::factory()->createOne(['current_team_id' => $team->id]);
    setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app()->instance('currentTeam', $team);

    actingAs($admin);

    $expectedPermissions = collect(Role::Admin->permissions())
        ->map(fn (Permission $p) => $p->value)
        ->sort()
        ->values()
        ->all();

    get(route('dashboard'))
        ->assertOk()
        /** @mago-expect analysis:non-documented-method */
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('currentTeam.id', $team->id)
                ->where('auth.user.permissions', $expectedPermissions),
        );
});

test('authenticated Member gets correct currentTeam, teams, and permissions', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme Corp', $owner);

    $member = User::factory()->createOne(['current_team_id' => $team->id]);
    setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app()->instance('currentTeam', $team);

    actingAs($member);

    $expectedPermissions = collect(Role::Member->permissions())
        ->map(fn (Permission $p) => $p->value)
        ->sort()
        ->values()
        ->all();

    get(route('dashboard'))
        ->assertOk()
        /** @mago-expect analysis:non-documented-method */
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('currentTeam.id', $team->id)
                ->where('auth.user.permissions', $expectedPermissions),
        );
});
