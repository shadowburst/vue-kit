<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\User;
use App\Services\Team\TeamContext;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('auth.subscription is null when team has no subscription', function (): void {
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
                ->where('auth.subscription', null),
        );
});

test('auth.subscription is null when subscription is active (not on grace period)', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Acme Corp', $user);
    $user->update(['current_team_id' => $team->id]);

    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly_test',
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app(TeamContext::class)->setTeam($team);

    actingAs($user);

    get(route('dashboard'))
        ->assertOk()
        /** @mago-expect analysis:non-documented-method */
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('auth.subscription', null),
        );
});

test('auth.subscription is null when on grace period but no non-owner members exceed free cap', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Acme Corp', $user);
    $user->update(['current_team_id' => $team->id]);

    // Owner only — nonOwnerCount = 0, freeCap = 0, so 0 <= 0 → no banner
    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly_test',
        'ends_at'       => CarbonImmutable::now()->addDays(14),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app(TeamContext::class)->setTeam($team);

    actingAs($user);

    get(route('dashboard'))
        ->assertOk()
        /** @mago-expect analysis:non-documented-method */
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('auth.subscription', null),
        );
});

test('auth.subscription carries grace_period when on grace period with non-owner members over free cap', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme Corp', $owner);
    $owner->update(['current_team_id' => $team->id]);

    // Add 2 non-owner members — nonOwnerCount = 2, freeCap = 0 → at_risk_count = 2
    $admin  = User::factory()->createOne(['current_team_id' => $team->id]);
    $member = User::factory()->createOne(['current_team_id' => $team->id]);

    setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);
    $member->assignRole(Role::Member->value);

    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly_test',
        'ends_at'       => CarbonImmutable::now()->addDays(14),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    app(TeamContext::class)->setTeam($team);

    actingAs($owner);

    get(route('dashboard'))
        ->assertOk()
        /** @mago-expect analysis:non-documented-method */
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('auth.subscription.grace_period')
                ->where('auth.subscription.grace_period.at_risk_count', 2)
                ->where('auth.subscription.grace_period.ends_at', fn (mixed $v) => is_string($v) && strlen($v) > 0),
        );
});

test('auth.subscription is null for a Member without subscription.view permission', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme Corp', $owner);

    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly_test',
        'ends_at'       => CarbonImmutable::now()->addDays(14),
    ]);

    actingAsMemberOf($team, Role::Member);

    app(TeamContext::class)->setTeam($team);

    get(route('dashboard'))
        ->assertOk()
        /** @mago-expect analysis:non-documented-method */
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('auth.subscription', null),
        );
});
