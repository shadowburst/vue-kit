<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Resource;

final class AuthForgotPasswordProps extends Resource
{
    public function __construct(
        public ?string $status,
    ) {}
}
