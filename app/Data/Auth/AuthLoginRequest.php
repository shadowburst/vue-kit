<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Enums\Validation\StringMaxLength;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class AuthLoginRequest extends Data
{
    public function __construct(
        public string $email,
        #[\SensitiveParameter]
        public string $password,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'email'    => ['required', 'string', 'email', StringMaxLength::Medium->maxRule()],
            'password' => ['required', 'string', StringMaxLength::Short->maxRule()],
        ];
    }

    /** @return array<string, string|array<array-key, mixed>|null> */
    public static function attributes(mixed ...$args): array
    {
        return [
            'email'    => __('auth.attributes.email'),
            'password' => __('auth.attributes.password'),
        ];
    }
}
