<?php

declare(strict_types=1);

namespace App\Data\Password;

use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PasswordUpdateRequest extends Data
{
    public function __construct(
        #[\SensitiveParameter]
        public string $current_password,
        #[\SensitiveParameter]
        public string $password,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password'         => ['required', 'string', Password::default(), 'confirmed'],
        ];
    }
}
