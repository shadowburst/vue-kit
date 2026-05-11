<?php

declare(strict_types=1);

namespace App\Data\Settings;

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
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore(Auth::id())],
        ];
    }
}
