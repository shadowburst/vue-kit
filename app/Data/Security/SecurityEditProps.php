<?php

declare(strict_types=1);

namespace App\Data\Security;

use Spatie\LaravelData\Resource;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class SecurityEditProps extends Resource
{
    public function __construct(
        public bool $canManageTwoFactor,
        public bool $twoFactorEnabled,
        public bool $requiresConfirmation,
    ) {}
}
