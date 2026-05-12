<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Concerns\WithTranslatedAttributes;
use App\Enums\Validation\StringMaxLength;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class AuthForgotPasswordRequest extends Data
{
    use WithTranslatedAttributes;

    public function __construct(
        public string $email,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'email' => ['required', 'string', 'email', StringMaxLength::Medium->maxRule()],
        ];
    }
}
