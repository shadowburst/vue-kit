<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Models\Team;
use App\Models\User;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Resource;

final class UserResource extends Resource
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $email_verified_at,
        public string $created_at,
        public ?int $current_team_id,
        /** @var Lazy|string[] */
        public Lazy|array $permissions,
        public Lazy|bool|null $is_owner,
        /** @var Lazy|array<int, array{id: int, name: string, slug: string, tier: string, features: array<string, mixed>}> */
        public Lazy|array|null $teams,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            id               : $user->id,
            name             : $user->name,
            email            : $user->email,
            email_verified_at: $user->email_verified_at?->toJSON(),
            created_at       : $user->created_at?->toJSON() ?? '',
            current_team_id  : $user->current_team_id,
            permissions      : Lazy::create(
                fn (): array => $user->getAllPermissions()->pluck('name')->sort()->values()->all(),
            )->defaultIncluded(),
            is_owner         : Lazy::whenLoaded(
                'currentTeam',
                $user,
                fn (): bool => $user->currentTeam?->owner_id === $user->id,
            ),
            teams            : Lazy::whenLoaded(
                'teams',
                $user,
                fn (): array => $user->teams->map(fn (Team $team): array => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'slug' => $team->slug,
                    'tier' => $team->tier()->value,
                    'features' => $team->features,
                ])->all(),
            ),
        );
    }
}
