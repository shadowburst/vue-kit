<?php

declare(strict_types=1);

namespace App\Data\Settings;

use App\Enums\Validation\StringMaxLength;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

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
            'current_password' => ['required', 'string', StringMaxLength::Short->maxRule(), 'current_password'],
            'password'         => [
                'required',
                'string',
                StringMaxLength::Short->maxRule(),
                Password::default(),
                'confirmed',
            ],
        ];
    }

    /** @return array<string, string|array<array-key, mixed>|null> */
    public static function attributes(mixed ...$args): array
    {
        return [
            'current_password' => __('settings.attributes.current_password'),
            'password'         => __('settings.attributes.password'),
        ];
    }
}
