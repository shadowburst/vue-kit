<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
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
            'token'    => ['required', 'string'],
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ];
    }
}
