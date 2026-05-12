<?php

declare(strict_types=1);

return [
    'title' => 'Settings',
    'description' => 'Manage your profile and account settings',
    'language' => 'Language',
    'language_settings' => 'Language settings',
    'language_description' => 'Choose your preferred language.',
    'save' => 'Save',

    'navigation' => [
        'profile' => 'Profile',
        'security' => 'Security',
        'appearance' => 'Appearance',
        'language' => 'Language',
    ],

    'profile' => [
        'title' => 'Profile settings',
        'information' => 'Profile information',
        'description' => 'Update your name and email address',
        'full_name' => 'Full name',
        'email_unverified' => 'Your email address is unverified.',
        'resend_verification' => 'Click here to resend the verification email.',
        'verification_sent' => 'A new verification link has been sent to your email address.',
        'member_since' => 'Member since :date',
    ],

    'appearance' => [
        'title' => 'Appearance settings',
        'description' => 'Update your account\'s appearance settings',
    ],

    'security' => [
        'title' => 'Security settings',
        'update_password' => 'Update password',
        'password_description' => 'Ensure your account is using a long, random password to stay secure',
        'save_password' => 'Save password',
        'two_factor_title' => 'Two-factor authentication',
        'two_factor_description' => 'Manage your two-factor authentication settings',
        'two_factor_disabled_body' => 'When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.',
        'two_factor_enabled_body' => 'You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.',
        'continue_setup' => 'Continue setup',
        'enable_two_factor' => 'Enable 2FA',
        'disable_two_factor' => 'Disable 2FA',
    ],

    'delete_account' => [
        'title' => 'Delete account',
        'description' => 'Delete your account and all of its resources',
        'warning' => 'Warning',
        'warning_body' => 'Please proceed with caution, this cannot be undone.',
        'confirm_title' => 'Are you sure you want to delete your account?',
        'confirm_description' => 'Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
    ],

    'attributes' => [
        'locale' => 'language',
        'current_password' => 'current password',
        'password' => 'password',
        'new_password' => 'new password',
        'password_confirmation' => 'password confirmation',
        'name' => 'name',
        'email' => 'email address',
    ],
];
