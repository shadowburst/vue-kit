<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class AuthForgotPasswordRequest extends Data
{
    public function __construct(
        public string $email,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }
}
