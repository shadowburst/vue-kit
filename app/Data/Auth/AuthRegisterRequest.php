<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Concerns\WithTranslatedAttributes;
use App\Enums\Validation\StringMaxLength;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class AuthRegisterRequest extends Data
{
    use WithTranslatedAttributes;

    public function __construct(
        public string $name,
        public string $email,
        #[\SensitiveParameter]
        public string $password,
        public string $password_confirmation,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'name'     => ['required', 'string', StringMaxLength::Short->maxRule()],
            'email'    => [
                'required',
                'string',
                'email',
                StringMaxLength::Medium->maxRule(),
                Rule::unique(User::class),
            ],
            'password' => ['required', 'string', StringMaxLength::Short->maxRule(), Password::default(), 'confirmed'],
        ];
    }
}
