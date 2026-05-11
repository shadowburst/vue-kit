<?php

declare(strict_types=1);

namespace App\Data\Settings;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class ProfileDeleteRequest extends Data
{
    public function __construct(
        #[\SensitiveParameter]
        public string $password,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'password' => ['required', 'string', 'current_password'],
        ];
    }
}
