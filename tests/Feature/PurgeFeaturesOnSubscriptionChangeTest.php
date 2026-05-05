<?php

declare(strict_types=1);

use App\Models\Team;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Pennant\Feature;

function subscriptionPayload(string $type, string $stripeId): array
{
    return [
        'type' => $type,
        'data' => ['object' => ['customer' => $stripeId]],
    ];
}

dataset('subscription_events', [
    'customer.subscription.created',
    'customer.subscription.updated',
    'customer.subscription.deleted',
]);

it('purges team Pennant feature cache on subscription event', function (string $eventType): void {
    $team = Team::factory()->create(['stripe_id' => 'cus_test123']);

    Feature::define('pro', fn (Team $t) => true);
    Feature::for($team)->active('pro'); // evaluate and store in DB

    $this->assertDatabaseHas('features', ['name' => 'pro']);

    event(new WebhookHandled(subscriptionPayload($eventType, 'cus_test123')));

    $this->assertDatabaseMissing('features', ['name' => 'pro']);
})->with('subscription_events');

it('purges only the affected team and not other teams', function (): void {
    $team = Team::factory()->create(['stripe_id' => 'cus_target']);
    $other = Team::factory()->create(['stripe_id' => 'cus_other']);

    Feature::define('pro', fn (Team $t) => true);
    Feature::for($team)->active('pro');
    Feature::for($other)->active('pro');

    event(new WebhookHandled(subscriptionPayload('customer.subscription.updated', 'cus_target')));

    $this->assertDatabaseMissing('features', [
        'name'  => 'pro',
        'scope' => (new Team)->getMorphClass().'|'.$team->id,
    ]);

    $this->assertDatabaseHas('features', [
        'name'  => 'pro',
        'scope' => (new Team)->getMorphClass().'|'.$other->id,
    ]);
});

it('is a no-op for unrelated webhook events', function (): void {
    $team = Team::factory()->create(['stripe_id' => 'cus_noop']);

    Feature::define('pro', fn (Team $t) => true);
    Feature::for($team)->active('pro');

    event(new WebhookHandled(subscriptionPayload('customer.updated', 'cus_noop')));

    $this->assertDatabaseHas('features', ['name' => 'pro']);
});
