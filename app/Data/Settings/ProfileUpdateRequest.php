<?php

declare(strict_types=1);

namespace App\Data\Settings;

use App\Enums\Validation\StringMaxLength;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class ProfileUpdateRequest extends Data
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'name'  => ['required', 'string', StringMaxLength::Short->maxRule()],
            'email' => [
                'required',
                'string',
                'email',
                StringMaxLength::Medium->maxRule(),
                Rule::unique(User::class)->ignore(Auth::id()),
            ],
        ];
    }

    /** @return array<string, string> */
    public static function attributes(mixed ...$args): array
    {
        return [
            'name'  => (string) __('settings.attributes.name'),
            'email' => (string) __('settings.attributes.email'),
        ];
    }
}
