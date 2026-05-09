<?php

declare(strict_types=1);

namespace App\Data\Team;

use App\Data\User\UserResource;
use App\Enums\Subscription\SubscriptionTier;
use App\Models\Team;
use App\Models\User;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Resource;

final class TeamResource extends Resource
{
    /**
     * @param  Lazy|array<string, mixed>  $features
     * @param  Lazy|array<int, UserResource>|null  $memberships
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public Lazy|SubscriptionTier $tier,
        public Lazy|array $features,
        public Lazy|int $members_count,
        public Lazy|UserResource|null $owner,
        public Lazy|array|null $memberships,
    ) {}

    public static function fromTeam(Team $team): self
    {
        return new self(
            id           : $team->id,
            name         : $team->name,
            slug         : $team->slug,
            tier         : Lazy::create(fn (): SubscriptionTier => $team->tier)
                ->defaultIncluded($team->hasAppended('tier')),
            features     : Lazy::create(fn (): array => $team->features)
                ->defaultIncluded($team->hasAppended('features')),
            members_count: Lazy::create(fn (): int => $team->members_count)
                ->defaultIncluded($team->hasAppended('members_count')),
            owner        : Lazy::whenLoaded(
                'owner',
                $team,
                fn (): UserResource => UserResource::from($team->owner),
            ),
            memberships  : Lazy::whenLoaded(
                'members',
                $team,
                fn (): array => $team
                    ->members
                    ->map(fn (User $user): UserResource => UserResource::from($user))
                    ->all(),
            ),
        );
    }
}
