<?php

declare(strict_types=1);

use App\Enums\SubscriptionTier;

test('level ordering: Free is 0, Pro is 1', function (): void {
    expect(SubscriptionTier::Free->level())->toBe(0)
        ->and(SubscriptionTier::Pro->level())->toBe(1);
});

test('atLeast: same tier satisfies itself', function (): void {
    expect(SubscriptionTier::Free->atLeast(SubscriptionTier::Free))->toBeTrue()
        ->and(SubscriptionTier::Pro->atLeast(SubscriptionTier::Pro))->toBeTrue();
});

test('atLeast: Pro satisfies a Free requirement', function (): void {
    expect(SubscriptionTier::Pro->atLeast(SubscriptionTier::Free))->toBeTrue();
});

test('atLeast: Free does not satisfy a Pro requirement', function (): void {
    expect(SubscriptionTier::Free->atLeast(SubscriptionTier::Pro))->toBeFalse();
});

test('stripeMonthlyId returns null for Free', function (): void {
    expect(SubscriptionTier::Free->stripeMonthlyId())->toBeNull();
});

test('stripeYearlyId returns null for Free', function (): void {
    expect(SubscriptionTier::Free->stripeYearlyId())->toBeNull();
});

test('stripeMonthlyId returns configured price ID for Pro', function (): void {
    config(['billing.tiers.pro.monthly' => 'price_pro_monthly_test']);

    expect(SubscriptionTier::Pro->stripeMonthlyId())->toBe('price_pro_monthly_test');
});

test('stripeYearlyId returns configured price ID for Pro', function (): void {
    config(['billing.tiers.pro.yearly' => 'price_pro_yearly_test']);

    expect(SubscriptionTier::Pro->stripeYearlyId())->toBe('price_pro_yearly_test');
});

test('fromStripePriceId resolves Pro from its monthly price ID', function (): void {
    config(['billing.tiers.pro.monthly' => 'price_pro_monthly_test']);

    expect(SubscriptionTier::fromStripePriceId('price_pro_monthly_test'))->toBe(SubscriptionTier::Pro);
});

test('fromStripePriceId resolves Pro from its yearly price ID', function (): void {
    config(['billing.tiers.pro.yearly' => 'price_pro_yearly_test']);

    expect(SubscriptionTier::fromStripePriceId('price_pro_yearly_test'))->toBe(SubscriptionTier::Pro);
});

test('fromStripePriceId defaults to Free for an unknown price ID', function (): void {
    config([
        'billing.tiers.pro.monthly' => 'price_pro_monthly_test',
        'billing.tiers.pro.yearly'  => 'price_pro_yearly_test',
    ]);

    expect(SubscriptionTier::fromStripePriceId('price_unknown'))->toBe(SubscriptionTier::Free);
});

test('fromStripePriceId defaults to Free for an empty string', function (): void {
    expect(SubscriptionTier::fromStripePriceId(''))->toBe(SubscriptionTier::Free);
});
