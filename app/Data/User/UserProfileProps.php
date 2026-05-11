<?php

declare(strict_types=1);

namespace App\Data\User;

use Spatie\LaravelData\Resource;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UserProfileProps extends Resource
{
    public function __construct(
        public bool $mustVerifyEmail,
        public ?string $status,
    ) {}
}
