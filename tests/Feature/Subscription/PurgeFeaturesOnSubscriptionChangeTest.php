<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Pennant\Feature;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

/**
 * @return array{type: string, data: array{object: array{customer: string}}}
 */
function subscriptionPayload(string $type, string $stripeId): array
{
    return [
        'type' => $type,
        'data' => ['object' => ['customer' => $stripeId]],
    ];
}

/**
 * @return array{type: string, data: array{object: array{customer: string, cancellation_details: array{reason: string}}}}
 */
function deletedSubscriptionPayload(string $stripeId, string $reason): array
{
    return [
        'type' => 'customer.subscription.deleted',
        'data' => [
            'object' => [
                'customer'             => $stripeId,
                'cancellation_details' => ['reason' => $reason],
            ],
        ],
    ];
}

dataset('subscription_events', [
    'customer.subscription.created',
    'customer.subscription.updated',
    'customer.subscription.deleted',
]);

it('purges team Pennant feature cache on subscription event', function (string $eventType): void {
    $team = Team::factory()->createOne(['stripe_id' => 'cus_test123']);

    Feature::define('pro', fn (Team $t) => true);
    Feature::for($team)->active('pro'); // evaluate and store in DB

    assertDatabaseHas('features', ['name' => 'pro']);

    event(new WebhookHandled(subscriptionPayload($eventType, 'cus_test123')));

    assertDatabaseMissing('features', ['name' => 'pro']);
})->with('subscription_events');

it('purges only the affected team and not other teams', function (): void {
    $team  = Team::factory()->createOne(['stripe_id' => 'cus_target']);
    $other = Team::factory()->createOne(['stripe_id' => 'cus_other']);

    Feature::define('pro', fn (Team $t) => true);
    Feature::for($team)->active('pro');
    Feature::for($other)->active('pro');

    event(new WebhookHandled(subscriptionPayload('customer.subscription.updated', 'cus_target')));

    assertDatabaseMissing('features', [
        'name'  => 'pro',
        'scope' => (new Team)->getMorphClass().'|'.$team->id,
    ]);

    assertDatabaseHas('features', [
        'name'  => 'pro',
        'scope' => (new Team)->getMorphClass().'|'.$other->id,
    ]);
});

it('is a no-op for unrelated webhook events', function (): void {
    $team = Team::factory()->createOne(['stripe_id' => 'cus_noop']);

    Feature::define('pro', fn (Team $t) => true);
    Feature::for($team)->active('pro');

    event(new WebhookHandled(subscriptionPayload('customer.updated', 'cus_noop')));

    assertDatabaseHas('features', ['name' => 'pro']);
});

it('detaches over-cap non-owner members on voluntary subscription deletion', function (): void {
    $owner           = User::factory()->createOne();
    $team            = (new CreateTeam)->execute('Acme Corp', $owner);
    $team->stripe_id = 'cus_voluntary_prune';
    $team->save();

    $member1 = User::factory()->createOne();
    $member2 = User::factory()->createOne();
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member1->assignRole(Role::Member->value);
    $member2->assignRole(Role::Member->value);

    assertDatabaseHas('model_has_roles', ['model_id' => $member1->id, 'team_id' => $team->id]);
    assertDatabaseHas('model_has_roles', ['model_id' => $member2->id, 'team_id' => $team->id]);

    event(new WebhookHandled(deletedSubscriptionPayload('cus_voluntary_prune', 'cancellation_requested')));

    assertDatabaseMissing('model_has_roles', ['model_id' => $member1->id, 'team_id' => $team->id]);
    assertDatabaseMissing('model_has_roles', ['model_id' => $member2->id, 'team_id' => $team->id]);
    assertDatabaseHas('model_has_roles', ['model_id' => $owner->id, 'team_id' => $team->id]);
});

it('Pennant cache is purged even on voluntary deletion alongside the prune', function (): void {
    $owner           = User::factory()->createOne();
    $team            = (new CreateTeam)->execute('Acme Corp', $owner);
    $team->stripe_id = 'cus_pennant_voluntary';
    $team->save();

    $member = User::factory()->createOne();
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    Feature::define('pro', fn (Team $t) => true);
    Feature::for($team)->active('pro');
    assertDatabaseHas('features', ['name' => 'pro']);

    event(new WebhookHandled(deletedSubscriptionPayload('cus_pennant_voluntary', 'cancellation_requested')));

    assertDatabaseMissing('features', ['name' => 'pro']);
    assertDatabaseMissing('model_has_roles', ['model_id' => $member->id, 'team_id' => $team->id]);
});

it('skips prune on involuntary subscription deletion and leaves members intact', function (): void {
    $owner           = User::factory()->createOne();
    $team            = (new CreateTeam)->execute('Acme Corp', $owner);
    $team->stripe_id = 'cus_involuntary_prune';
    $team->save();

    $member = User::factory()->createOne();
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    Feature::define('pro', fn (Team $t) => true);
    Feature::for($team)->active('pro');

    event(new WebhookHandled(deletedSubscriptionPayload('cus_involuntary_prune', 'payment_failed')));

    assertDatabaseHas('model_has_roles', ['model_id' => $member->id, 'team_id' => $team->id]);
    assertDatabaseMissing('features', ['name' => 'pro']); // Pennant still purged
});

it('is idempotent when the team is already at or under cap on voluntary deletion', function (): void {
    $owner           = User::factory()->createOne();
    $team            = (new CreateTeam)->execute('Acme Corp', $owner);
    $team->stripe_id = 'cus_idempotent_prune';
    $team->save();

    // No non-owner members — already at the free cap of 0.
    assertDatabaseHas('model_has_roles', ['model_id' => $owner->id, 'team_id' => $team->id]);

    event(new WebhookHandled(deletedSubscriptionPayload('cus_idempotent_prune', 'cancellation_requested')));

    assertDatabaseHas('model_has_roles', ['model_id' => $owner->id, 'team_id' => $team->id]);
});
