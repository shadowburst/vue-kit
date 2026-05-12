<?php

declare(strict_types=1);

namespace App\Services\Impersonation;

use App\Models\User;
use Lab404\Impersonate\Services\ImpersonateManager;

final class ImpersonationContext
{
    public function __construct(
        private readonly ImpersonateManager $manager,
    ) {}

    public function isImpersonating(): bool
    {
        return $this->manager->isImpersonating();
    }

    public function impersonator(): ?User
    {
        if (! $this->isImpersonating()) {
            return null;
        }

        /** @var User|null */
        return $this->manager->getImpersonator();
    }
}
