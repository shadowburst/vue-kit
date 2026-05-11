<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Enums\Subscription\SubscriptionInterval;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class TeamCheckoutRequest extends Data
{
    public function __construct(
        public SubscriptionInterval $interval,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'interval' => ['required', new Enum(SubscriptionInterval::class)],
        ];
    }
}
