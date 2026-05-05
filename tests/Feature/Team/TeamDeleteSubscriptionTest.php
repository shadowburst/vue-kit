<?php

declare(strict_types=1);

use App\Models\Team;
use Stripe\StripeClient;

it('cancels active subscription immediately when team is deleted', function (): void {
    $team = Team::factory()->create();
    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly',
    ]);

    $fakeSubscriptions = new class {
        public int $cancelCount = 0;

        public function cancel(string $id, array $params = []): void
        {
            $this->cancelCount++;
        }
    };

    app()->bind(StripeClient::class, fn () => (object) ['subscriptions' => $fakeSubscriptions]);

    $team->delete();

    expect($fakeSubscriptions->cancelCount)->toBe(1);
    $this->assertDatabaseMissing('teams', ['id' => $team->id]);
});

it('performs no stripe call when deleting team without subscription', function (): void {
    $team = Team::factory()->create();

    $fakeSubscriptions = new class {
        public int $cancelCount = 0;

        public function cancel(string $id, array $params = []): void
        {
            $this->cancelCount++;
        }
    };

    app()->bind(StripeClient::class, fn () => (object) ['subscriptions' => $fakeSubscriptions]);

    $team->delete();

    expect($fakeSubscriptions->cancelCount)->toBe(0);
    $this->assertDatabaseMissing('teams', ['id' => $team->id]);
});
