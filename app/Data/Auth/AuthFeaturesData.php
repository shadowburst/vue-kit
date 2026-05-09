<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Enums\Feature\Feature;
use App\Models\Team;
use Laravel\Pennant\Feature as PennantFeature;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class AuthFeaturesData extends Data
{
    public function __construct(
        public int $teamMemberCap,
    ) {}

    public static function fromTeam(?Team $team): self
    {
        if ($team === null) {
            return new self(teamMemberCap: 0);
        }

        return new self(
            teamMemberCap: (int) PennantFeature::for($team)->value(Feature::TeamMemberCap->value),
        );
    }
}
