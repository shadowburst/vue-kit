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

test('billing page renders Pro state for an Owner of a Pro team', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Test Team', $user);
    $user->update(['current_team_id' => $team->id]);

    config([
        'billing.tiers.pro.monthly' => 'price_pro_monthly_test',
        'billing.tiers.pro.yearly'  => 'price_pro_yearly_test',
    ]);

    $team->forceFill(['pm_last_four' => '4242'])->save();
    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly_test',
    ]);

    $response = actingAs($user)->get(route('teams.billing.show'));

    $response->assertOk();
    /** @mago-expect analysis:non-documented-method */
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('settings/team/Billing')
        ->where('tier', 'pro')
        ->where('interval', 'monthly')
        ->where('subscriptionStatus', 'active')
        ->where('pmLastFour', '4242')
        ->where('nextChargeDate', null)
        ->where('nextChargeAmount', null),
    );
});

test('billing page renders Pro state read-only for an Admin (no subscription.update)', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Test Team', $owner);

    config([
        'billing.tiers.pro.monthly' => 'price_pro_monthly_test',
        'billing.tiers.pro.yearly'  => 'price_pro_yearly_test',
    ]);

    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly_test',
    ]);

    $admin = User::factory()->createOne(['current_team_id' => $team->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    $response = actingAs($admin)->get(route('teams.billing.show'));

    $response->assertOk();
    /** @mago-expect analysis:non-documented-method */
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('settings/team/Billing')
        ->where('tier', 'pro')
        ->where('interval', 'monthly')
        ->where('auth.abilities.subscription.update', false),
    );
});
