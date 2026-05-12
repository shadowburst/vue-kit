<?php

declare(strict_types=1);

namespace App\Data\Settings;

use Spatie\LaravelData\Resource;

final class SecurityEditProps extends Resource
{
    public function __construct(
        public bool $canManageTwoFactor,
        public bool $twoFactorEnabled,
        public bool $requiresConfirmation,
    ) {}
}
