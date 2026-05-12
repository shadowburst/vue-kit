<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Resource;

final class AuthLoginProps extends Resource
{
    public function __construct(
        #[\SensitiveParameter]
        public bool $canResetPassword,
        public bool $canRegister,
        public ?string $status,
    ) {}
}
