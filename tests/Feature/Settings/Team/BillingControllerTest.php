<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
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

test('billing page renders Free state for an Owner of a Free team', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Test Team', $user);
    $user->update(['current_team_id' => $team->id]);

    $response = actingAs($user)->get(route('teams.billing.show'));

    $response->assertOk();
    /** @mago-expect analysis:non-documented-method */
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('settings/team/Billing')
        ->where('tier', 'free')
        ->where('interval', null),
    );
});

test('billing page renders for an Admin (subscription.view only)', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Test Team', $owner);

    $admin = User::factory()->createOne(['current_team_id' => $team->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    $response = actingAs($admin)->get(route('teams.billing.show'));

    $response->assertOk();
    /** @mago-expect analysis:non-documented-method */
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('settings/team/Billing'),
    );
});

test('billing page returns 403 for a Member', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Test Team', $owner);

    $member = User::factory()->createOne(['current_team_id' => $team->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    $response = actingAs($member)->get(route('teams.billing.show'));

    $response->assertForbidden();
});

test('billing page redirects unauthenticated user to login', function (): void {
    $response = get(route('teams.billing.show'));

    $response->assertRedirect(route('login'));
});
