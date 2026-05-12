<?php

declare(strict_types=1);

namespace App\Data\Settings;

use Spatie\LaravelData\Resource;

final class ProfileEditProps extends Resource
{
    public function __construct(
        public bool $mustVerifyEmail,
        public ?string $status,
    ) {}
}
