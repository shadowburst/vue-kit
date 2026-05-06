<?php

declare(strict_types=1);

namespace App\Enums\Subscription;

enum SubscriptionInterval: string
{
    case Monthly = 'monthly';
    case Yearly  = 'yearly';
}
