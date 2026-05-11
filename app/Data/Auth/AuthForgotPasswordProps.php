<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Resource;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class AuthForgotPasswordProps extends Resource
{
    public function __construct(
        public ?string $status,
    ) {}
}
