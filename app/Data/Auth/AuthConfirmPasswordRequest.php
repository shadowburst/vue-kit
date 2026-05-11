<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Enums\Validation\StringMaxLength;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class AuthConfirmPasswordRequest extends Data
{
    public function __construct(
        #[\SensitiveParameter]
        public string $password,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'password' => ['required', 'string', StringMaxLength::Short->maxRule(), 'current_password'],
        ];
    }
}
