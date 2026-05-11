<?php

declare(strict_types=1);

namespace App\Data\Settings;

use App\Enums\Settings\Locale;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class LocaleUpdateRequest extends Data
{
    public function __construct(
        public Locale $locale,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'locale' => ['required', Rule::enum(Locale::class)],
        ];
    }
}
