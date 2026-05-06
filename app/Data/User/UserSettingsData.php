<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Enums\Settings\Locale;
use Spatie\LaravelData\Data;

final class UserSettingsData extends Data
{
    public function __construct(
        public Locale $locale = Locale::Fr,
    ) {}
}
