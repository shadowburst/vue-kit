<?php

declare(strict_types=1);

namespace App\Data\Shared;

use App\Data\Auth\AuthAbilitiesData;
use App\Data\User\UserResource;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class SharedAuthData extends Data
{
    /**
     * @param  array<string, mixed>  $features
     * @param  array{grace_period: array{ends_at: string|null, at_risk_count: int}}|null  $subscription
     */
    public function __construct(
        public ?UserResource $user,
        public AuthAbilitiesData $abilities,
        public array $features,
        public ?array $subscription,
    ) {}
}
