<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Permission\Permission;
use App\Enums\Role\Role;
use App\Models\User;
use App\Services\Team\TeamContext;
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
                ->where('auth.abilities.user.view_any', false)
                ->where('auth.abilities.user.view', false)
                ->where('auth.abilities.user.create', false)
                ->where('auth.abilities.user.update', false)
                ->where('auth.abilities.user.delete', false)
                ->where('auth.abilities.team.view', false)
                ->where('auth.abilities.team.update', false)
                ->where('auth.abilities.team.delete', false)
                ->where('auth.abilities.subscription.view', false)
                ->where('auth.abilities.subscription.update', false)
                ->where('auth.features', []),
        );
});

// The remaining tests pre-set TeamContext and setPermissionsTeamId directly
// because teams.create (the only auth-only Inertia route) is exempt from SetCurrentTeam
// to prevent redirect loops — SetCurrentTeam's own test covers that flow.

test('authenticated Owner gets correct currentTeam, teams, and permissions', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Acme Corp', $user);
    $user->update(['current_team_id' => $team->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app(TeamContext::class)->setTeam($team);

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
                ->where('auth.abilities.user.view_any', true)
                ->where('auth.abilities.user.view', true)
                ->where('auth.abilities.user.create', true)
                ->where('auth.abilities.user.update', true)
                ->where('auth.abilities.user.delete', true)
                ->where('auth.abilities.team.view', true)
                ->where('auth.abilities.team.update', true)
                ->where('auth.abilities.team.delete', true)
                ->where('auth.abilities.subscription.view', true)
                ->where('auth.abilities.subscription.update', true)
                ->where('auth.features', []),
        );
});

test('authenticated Admin gets correct currentTeam, teams, and permissions', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme Corp', $owner);

    $admin = User::factory()->createOne(['current_team_id' => $team->id]);
    setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app(TeamContext::class)->setTeam($team);

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
    app(TeamContext::class)->setTeam($team);

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
