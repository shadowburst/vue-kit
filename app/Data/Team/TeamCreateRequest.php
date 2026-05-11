<?php

declare(strict_types=1);

namespace App\Data\Team;

use App\Enums\Validation\StringMaxLength;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class TeamCreateRequest extends Data
{
    public function __construct(
        public string $name,
    ) {}

    /** @return array<string, mixed> */
    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'name' => ['required', 'string', StringMaxLength::Short->maxRule()],
        ];
    }
}
