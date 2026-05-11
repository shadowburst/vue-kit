<?php

declare(strict_types=1);

namespace App\Data\Locale;

use App\Enums\Settings\Locale;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class LocaleUpdateRequest extends Data
{
    public function __construct(
        public Locale $locale,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'locale' => ['required', Rule::enum(Locale::class)],
        ];
    }
}
