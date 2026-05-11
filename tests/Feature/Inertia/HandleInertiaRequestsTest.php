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

test('currentTeam prop is shaped by TeamResource for authenticated user', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Acme Corp', $user);
    $user->update(['current_team_id' => $team->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app(TeamContext::class)->setTeam($team);

    actingAs($user);

    get(route('dashboard'))
        ->assertOk()
        /** @mago-expect analysis:non-documented-method */
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('currentTeam.id', $team->id)
                ->where('currentTeam.name', 'Acme Corp')
                ->where('currentTeam.slug', 'acme-corp')
                ->where('currentTeam.tier', 'free')
                ->has('currentTeam.features')
                ->missing('currentTeam.stripe_id')
                ->missing('currentTeam.owner_id'),
        );
});

test('currentTeam lazy relations are absent when not loaded', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Acme Corp', $user);
    $user->update(['current_team_id' => $team->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app(TeamContext::class)->setTeam($team);

    actingAs($user);

    get(route('dashboard'))
        ->assertOk()
        /** @mago-expect analysis:non-documented-method */
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->missing('currentTeam.owner')
                ->missing('currentTeam.memberships'),
        );
});

test('auth.user prop is shaped by UserResource and includes loaded teams', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Acme Corp', $user);
    $user->update(['current_team_id' => $team->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app(TeamContext::class)->setTeam($team);

    actingAs($user);

    get(route('dashboard'))
        ->assertOk()
        /** @mago-expect analysis:non-documented-method */
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('auth.user.id', $user->id)
                ->where('auth.user.name', $user->name)
                ->where('auth.user.email', $user->email)
                ->where('auth.user.current_team_id', $team->id)
                ->has('auth.user.teams', 1)           // Lazy::whenLoaded — teams loaded
                ->has('auth.user.permissions')         // Lazy::create — always included
                ->missing('auth.user.password')
                ->missing('auth.user.remember_token')
                ->missing('auth.user.two_factor_secret'),
        );
});

test('auth.user includes is_owner lazy field when currentTeam is loaded', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Acme Corp', $user);
    $user->update(['current_team_id' => $team->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app(TeamContext::class)->setTeam($team);

    actingAs($user);

    get(route('dashboard'))
        ->assertOk()
        /** @mago-expect analysis:non-documented-method */
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('auth.user.is_owner', true),  // Lazy::whenLoaded('currentTeam') — included when loaded
        );
});

test('authenticated team creator (Admin + owner_id) gets correct currentTeam, teams, and permissions', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Acme Corp', $user);
    $user->update(['current_team_id' => $team->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app(TeamContext::class)->setTeam($team);

    actingAs($user);

    $expectedPermissions = collect(Role::Manager->permissions())
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
                ->where('auth.abilities.user.create', false) // Free tier: cap=0, invite blocked
                ->where('auth.abilities.user.update', true)
                ->where('auth.abilities.user.delete', true)
                ->where('auth.abilities.team.view', true) // Admin holds Permission::TeamView
                ->where('auth.abilities.team.update', true) // owner_id identity check
                ->where('auth.abilities.team.delete', false) // sole owned team — personal-team rule
                ->where('auth.abilities.subscription.view', true)
                ->where('auth.abilities.subscription.update', true) // owner_id identity check
                ->where('auth.features', ['team-member-cap' => 0]), // Free tier → cap=0
        );
});

test('authenticated Admin gets correct currentTeam, teams, and permissions', function (): void {
    $owner = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Acme Corp', $owner);

    $admin = User::factory()->createOne(['current_team_id' => $team->id]);
    setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Manager->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app(TeamContext::class)->setTeam($team);

    actingAs($admin);

    $expectedPermissions = collect(Role::Manager->permissions())
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
    $team = (new CreateTeam)->execute('Acme Corp', $owner);

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
