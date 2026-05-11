<?php

declare(strict_types=1);

namespace App\Data\Team;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\LaravelData\Resource;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class TeamIndexProps extends Resource
{
    /**
     * @param  PaginatedDataCollection<int, TeamResource>  $teams
     */
    public function __construct(
        #[DataCollectionOf(TeamResource::class)]
        public PaginatedDataCollection $teams,
    ) {}
}
