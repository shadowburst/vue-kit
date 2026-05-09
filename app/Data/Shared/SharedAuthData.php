<?php

declare(strict_types=1);

namespace App\Data\Shared;

use App\Data\Auth\AuthAbilitiesData;
use App\Data\Auth\AuthFeaturesData;
use App\Data\Auth\AuthSubscriptionData;
use App\Data\User\UserResource;
use Spatie\LaravelData\Data;

final class SharedAuthData extends Data
{
    public function __construct(
        public ?UserResource $user,
        public AuthAbilitiesData $abilities,
        public AuthFeaturesData $features,
        public ?AuthSubscriptionData $subscription,
    ) {}
}
