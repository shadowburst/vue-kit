<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Models\Team;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class AuthSubscriptionData extends Data
{
    public function __construct(
        public bool $active,
    ) {}

    public static function fromTeam(?Team $team): ?self
    {
        if ($team === null) {
            return null;
        }

        $subscription = $team->subscription('default');

        if ($subscription === null) {
            return new self(active: false);
        }

        return new self(active: ! $subscription->ended());
    }
}
