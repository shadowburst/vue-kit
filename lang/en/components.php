<?php

declare(strict_types=1);

return [
    'alert_error' => [
        'title' => 'Something went wrong.',
    ],
    'appearance' => [
        'light' => 'Light',
        'dark' => 'Dark',
        'system' => 'System',
    ],
    'navigation' => [
        'menu' => 'Navigation menu',
        'platform' => 'Platform',
    ],
    'password_input' => [
        'show' => 'Show password',
        'hide' => 'Hide password',
    ],
    'two_factor_recovery_codes' => [
        'title' => '2FA recovery codes',
        'description' => 'Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.',
        'view' => 'View recovery codes',
        'hide' => 'Hide recovery codes',
        'regenerate' => 'Regenerate codes',
        'help' => 'Each recovery code can be used once to access your account and will be removed after use. If you need more, click :action above.',
    ],
    'two_factor_setup_modal' => [
        'enabled_title' => 'Two-factor authentication enabled',
        'enabled_description' => 'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
        'verify_title' => 'Verify authentication code',
        'verify_description' => 'Enter the 6-digit code from your authenticator app',
        'enable_title' => 'Enable two-factor authentication',
        'enable_description' => 'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app',
        'manual_entry' => 'or, enter the code manually',
    ],
    'ui' => [
        'custom' => [
            'confirm_dialog' => [
                'title' => [
                    'default' => 'Are you sure?',
                    'destructive' => 'Are you sure?',
                ],
            ],
        ],
    ],
];
