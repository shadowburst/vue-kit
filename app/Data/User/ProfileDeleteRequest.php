<?php

declare(strict_types=1);

namespace App\Data\User;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class ProfileDeleteRequest extends Data
{
    public function __construct(
        public string $password,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password'],
        ];
    }
}
