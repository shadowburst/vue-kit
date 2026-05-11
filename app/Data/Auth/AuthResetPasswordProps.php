<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Resource;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class AuthResetPasswordProps extends Resource
{
    public function __construct(
        public string $token,
        public string $email,
    ) {}
}
