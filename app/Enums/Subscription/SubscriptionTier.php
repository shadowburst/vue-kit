<?php

declare(strict_types=1);

namespace App\Enums\Subscription;

use Illuminate\Support\Facades\Config;

enum SubscriptionTier: string
{
    case Free = 'free';
    case Pro  = 'pro';

    public function level(): int
    {
        return match ($this) {
            self::Free => 0,
            self::Pro => 1,
        };
    }

    public function atLeast(self $tier): bool
    {
        return $this->level() >= $tier->level();
    }

    public function stripeMonthlyId(): ?string
    {
        if ($this === self::Free) {
            return null;
        }

        $id = Config::string("billing.tiers.{$this->value}.monthly");

        return $id !== '' ? $id : null;
    }

    public function stripeYearlyId(): ?string
    {
        if ($this === self::Free) {
            return null;
        }

        $id = Config::string("billing.tiers.{$this->value}.yearly");

        return $id !== '' ? $id : null;
    }

    public static function fromStripePriceId(string $priceId): self
    {
        if ($priceId === '') {
            return self::Free;
        }

        foreach (self::cases() as $tier) {
            if ($tier->stripeMonthlyId() === $priceId || $tier->stripeYearlyId() === $priceId) {
                return $tier;
            }
        }

        return self::Free;
    }
}
