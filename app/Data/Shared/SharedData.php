<?php

declare(strict_types=1);

namespace App\Data\Shared;

use App\Data\Team\TeamResource;
use Spatie\LaravelData\Resource;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class SharedData extends Resource
{
    public function __construct(
        public string $name,
        public SharedAuthData $auth,
        public ?TeamResource $currentTeam,
        public bool $sidebarOpen,
        public string $locale,
    ) {}
}
