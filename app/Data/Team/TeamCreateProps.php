<?php

declare(strict_types=1);

namespace App\Data\Team;

use Spatie\LaravelData\Resource;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class TeamCreateProps extends Resource
{
    public function __construct() {}
}
