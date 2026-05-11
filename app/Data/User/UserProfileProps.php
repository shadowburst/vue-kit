<?php

declare(strict_types=1);

namespace App\Data\User;

use Spatie\LaravelData\Resource;

final class UserProfileProps extends Resource
{
    public function __construct(
        public bool $mustVerifyEmail,
        public ?string $status,
    ) {}
}
