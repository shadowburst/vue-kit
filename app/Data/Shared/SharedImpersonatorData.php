<?php

declare(strict_types=1);

namespace App\Data\Shared;

use Spatie\LaravelData\Data;

final class SharedImpersonatorData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}
