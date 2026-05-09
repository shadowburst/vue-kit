<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Tier → Stripe Price ID Map
    |--------------------------------------------------------------------------
    |
    | Maps each paid tier to its Stripe Price IDs for monthly and yearly
    | billing intervals. Free is the absence of an active subscription
    | and has no Stripe Price IDs.
    |
    */

    'tiers' => [

        'free' => [
            'member_cap' => 0,
        ],

        'pro' => [
            'monthly'    => (string) env('STRIPE_PRICE_PRO_MONTHLY', ''),
            'yearly'     => (string) env('STRIPE_PRICE_PRO_YEARLY', ''),
            'member_cap' => 3,
        ],

    ],

];
