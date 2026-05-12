<?php

declare(strict_types=1);

return [
    'title' => 'Billing',
    'description' => 'Manage your team\'s subscription.',
    'current_tier' => 'Current plan',
    'tier_free' => 'Free',
    'tier_pro' => 'Pro',
    'interval_monthly' => 'Monthly',
    'interval_yearly' => 'Yearly',
    'upgrade_to_pro' => 'Upgrade to Pro',
    'status_grace' => 'Cancellation scheduled',
    'billing_interval' => 'Billing interval',
    'next_charge' => 'Next charge',
    'payment_method' => 'Payment method',
    'manage_billing' => 'Manage Billing',
    'cancel_subscription' => 'Cancel Subscription',
    'cancel_over_cap'     => '{1} Remove :count member to cancel|[2,*] Remove :count members to cancel',
    'resume_subscription' => 'Resume Subscription',

    'over_cap_banner_title'         => 'Your team is over the member cap',
    'over_cap_banner_body_active'   => '{1} Remove :count member to restore access.|[2,*] Remove :count members to restore access.',
    'over_cap_banner_body_canceled' => 'Fix your payment to restore access.',

    'attributes' => [
        'interval' => 'billing interval',
    ],
];
