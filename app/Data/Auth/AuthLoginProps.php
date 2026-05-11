<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Resource;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class AuthLoginProps extends Resource
{
    public function __construct(
        public bool $canResetPassword,
        public bool $canRegister,
        public ?string $status,
    ) {}
}
