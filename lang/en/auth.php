<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'login' => [
        'title' => 'Log in',
        'layout_title' => 'Log in to your account',
        'description' => 'Enter your email and password below to log in',
        'forgot_password' => 'Forgot password?',
        'remember_me' => 'Remember me',
        'no_account' => 'Don\'t have an account?',
        'sign_up' => 'Sign up',
    ],

    'register' => [
        'title' => 'Register',
        'layout_title' => 'Create an account',
        'description' => 'Enter your details below to create your account',
        'submit' => 'Create account',
        'already_registered' => 'Already have an account?',
    ],

    'forgot_password' => [
        'title' => 'Forgot password',
        'description' => 'Enter your email to receive a password reset link',
        'submit' => 'Email password reset link',
        'return_to' => 'Or, return to',
    ],

    'reset_password' => [
        'title' => 'Reset password',
        'description' => 'Please enter your new password below',
    ],

    'confirm_password' => [
        'title' => 'Confirm password',
        'layout_title' => 'Confirm your password',
        'description' => 'This is a secure area of the application. Please confirm your password before continuing.',
    ],

    'verify_email' => [
        'title' => 'Email verification',
        'layout_title' => 'Verify email',
        'description' => 'Please verify your email address by clicking on the link we just emailed to you.',
        'sent' => 'A new verification link has been sent to the email address you provided during registration.',
        'resend' => 'Resend verification email',
    ],

    'two_factor_challenge' => [
        'title' => 'Two-factor authentication',
        'recovery_title' => 'Recovery code',
        'recovery_description' => 'Please confirm access to your account by entering one of your emergency recovery codes.',
        'authentication_title' => 'Authentication code',
        'authentication_description' => 'Enter the authentication code provided by your authenticator application.',
        'use_authentication_code' => 'login using an authentication code',
        'use_recovery_code' => 'login using a recovery code',
        'or_you_can' => 'or you can',
        'recovery_code_placeholder' => 'Enter recovery code',
    ],

    'placeholders' => [
        'email' => 'email@example.com',
    ],

    'attributes' => [
        'email' => 'email address',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'name' => 'name',
        'token' => 'reset token',
        'current_password' => 'current password',
        'new_password' => 'new password',
    ],

];
