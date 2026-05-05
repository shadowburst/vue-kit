<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AppLocale;
use Spatie\LaravelData\Data;

final class UserSettingsData extends Data
{
    public function __construct(
        public AppLocale $locale,
    ) {}
}
