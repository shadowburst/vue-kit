<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Enums\Validation\StringMaxLength;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class AuthResetPasswordRequest extends Data
{
    public function __construct(
        #[\SensitiveParameter]
        public string $token,
        public string $email,
        #[\SensitiveParameter]
        public string $password,
        public string $password_confirmation,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'token'    => ['required', 'string', StringMaxLength::Medium->maxRule()],
            'email'    => ['required', 'string', 'email', StringMaxLength::Medium->maxRule()],
            'password' => ['required', 'string', StringMaxLength::Short->maxRule(), Password::default(), 'confirmed'],
        ];
    }

    /** @return array<string, string> */
    public static function attributes(mixed ...$args): array
    {
        return [
            'token'                 => (string) __('auth.attributes.token'),
            'email'                 => (string) __('auth.attributes.email'),
            'password'              => (string) __('auth.attributes.password'),
            'password_confirmation' => (string) __('auth.attributes.password_confirmation'),
        ];
    }
}
