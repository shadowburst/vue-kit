<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Enums\Permission\Permission;
use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class AuthAbilitiesData extends Data
{
    public function __construct(
        /** @var array{view_any: bool, view: bool, create: bool, update: bool, delete: bool} */
        public array $user,
        /** @var array{view: bool, update: bool, delete: bool} */
        public array $team,
        /** @var array{view: bool, update: bool} */
        public array $subscription,
    ) {}

    public static function fromUser(?User $user = null): self
    {
        return new self(
            user        : [
                'view_any' => $user?->can(Permission::UserViewAny->value) ?? false,
                'view'     => $user?->can(Permission::UserView->value) ?? false,
                'create'   => $user?->can(Permission::UserCreate->value) ?? false,
                'update'   => $user?->can(Permission::UserUpdate->value) ?? false,
                'delete'   => $user?->can(Permission::UserDelete->value) ?? false,
            ],
            team        : [
                'view'   => $user?->can(Permission::TeamView->value) ?? false,
                'update' => $user?->can(Permission::TeamUpdate->value) ?? false,
                'delete' => $user?->can(Permission::TeamDelete->value) ?? false,
            ],
            subscription: [
                'view'   => $user?->can(Permission::SubscriptionView->value) ?? false,
                'update' => $user?->can(Permission::SubscriptionUpdate->value) ?? false,
            ],
        );
    }
}
