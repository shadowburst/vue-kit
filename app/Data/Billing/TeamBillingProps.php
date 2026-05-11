<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Enums\Subscription\SubscriptionTier;
use Spatie\LaravelData\Resource;

final class TeamBillingProps extends Resource
{
    public function __construct(
        public SubscriptionTier $tier,
        public ?string $interval,
        public ?string $subscriptionStatus,
        public ?string $pmLastFour,
        public ?string $nextChargeDate,
        public ?string $nextChargeAmount,
    ) {}
}
