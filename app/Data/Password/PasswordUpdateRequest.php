<?php

declare(strict_types=1);

namespace App\Data\Password;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Illuminate\Validation\Rules\Password;

#[TypeScript]
final class PasswordUpdateRequest extends Data
{
    public function __construct(
        public string $current_password,
        public string $password,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password'         => ['required', 'string', Password::default(), 'confirmed'],
        ];
    }
}
