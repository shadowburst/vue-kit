<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Permission\PermissionName;
use App\Enums\Role\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed(RolePermissionSeeder::class);
});

test('guest request shares null currentTeam and null auth.user', function (): void {
    get(route('login'))
        ->assertOk()
        /** @mago-expect analysis:non-documented-method */
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('currentTeam', null)
                ->where('auth.user', null),
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

    $expectedPermissions = collect(RoleName::Owner->permissions())
        ->map(fn (PermissionName $p) => $p->value)
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
                ->where('auth.user.permissions', $expectedPermissions),
        );
});

test('authenticated Admin gets correct currentTeam, teams, and permissions', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme Corp', $owner);

    $admin = User::factory()->createOne(['current_team_id' => $team->id]);
    setPermissionsTeamId($team->id);
    $admin->assignRole(RoleName::Admin->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app()->instance('currentTeam', $team);

    actingAs($admin);

    $expectedPermissions = collect(RoleName::Admin->permissions())
        ->map(fn (PermissionName $p) => $p->value)
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
    $member->assignRole(RoleName::Member->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app()->instance('currentTeam', $team);

    actingAs($member);

    $expectedPermissions = collect(RoleName::Member->permissions())
        ->map(fn (PermissionName $p) => $p->value)
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
