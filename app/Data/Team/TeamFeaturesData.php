<?php

declare(strict_types=1);

namespace App\Data\Team;

use App\Models\Team;
use Laravel\Pennant\Feature;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class TeamFeaturesData extends Data
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public array $values,
    ) {}

    public static function fromTeam(Team $team): self
    {
        return new self(
            values: Feature::for($team)->all(),
        );
    }
}
