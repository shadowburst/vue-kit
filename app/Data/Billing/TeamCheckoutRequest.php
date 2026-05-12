<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Concerns\WithTranslatedAttributes;
use App\Enums\Subscription\SubscriptionInterval;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class TeamCheckoutRequest extends Data
{
    use WithTranslatedAttributes;

    public function __construct(
        public SubscriptionInterval $interval,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'interval' => ['required', new Enum(SubscriptionInterval::class)],
        ];
    }
}
