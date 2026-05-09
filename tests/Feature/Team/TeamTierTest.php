<?php

declare(strict_types=1);

use App\Enums\Subscription\SubscriptionTier;
use App\Models\Team;

beforeEach(function (): void {
    config([
        'billing.tiers.pro.monthly' => 'price_pro_monthly',
        'billing.tiers.pro.yearly'  => 'price_pro_yearly',
    ]);
});

test('tier returns Free when team has no subscription', function (): void {
    $team = Team::factory()->createOne();

    expect($team->tier)->toBe(SubscriptionTier::Free);
});

test('tier returns Pro for an active monthly Pro subscription', function (): void {
    $team = Team::factory()->createOne();
    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_pro_monthly_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly',
    ]);

    expect($team->tier)->toBe(SubscriptionTier::Pro);
});

test('tier returns Pro for an active yearly Pro subscription', function (): void {
    $team = Team::factory()->createOne();
    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_pro_yearly_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_yearly',
    ]);

    expect($team->tier)->toBe(SubscriptionTier::Pro);
});

test('tier returns Free after cancellation past grace period', function (): void {
    $team = Team::factory()->createOne();
    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_cancelled_test',
        'stripe_status' => 'canceled',
        'stripe_price'  => 'price_pro_monthly',
        'ends_at'       => now()->subDay(),
    ]);

    expect($team->tier)->toBe(SubscriptionTier::Free);
});

test('tier returns Free (defensive) when the active subscription price is not in the config map', function (): void {
    $team = Team::factory()->createOne();
    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_unknown_price_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_unknown_tier',
    ]);

    expect($team->tier)->toBe(SubscriptionTier::Free);
});
