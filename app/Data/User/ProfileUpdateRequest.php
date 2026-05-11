<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Models\User;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class ProfileUpdateRequest extends Data
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore(auth()->id())],
        ];
    }
}
