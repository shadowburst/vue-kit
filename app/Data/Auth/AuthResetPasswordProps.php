<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Resource;

final class AuthResetPasswordProps extends Resource
{
    public function __construct(
        #[\SensitiveParameter]
        public string $token,
        public string $email,
    ) {}
}
