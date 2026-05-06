<?php

declare(strict_types=1);

namespace App\Data\User;

use App\Enums\Permission\Permission;
use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UserAbilitiesData extends Data
{
    /**
     * @param  array{viewAny: bool, view: bool, create: bool, update: bool, delete: bool}  $user
     * @param  array{view: bool, update: bool, delete: bool}  $team
     * @param  array{view: bool, update: bool}  $subscription
     */
    public function __construct(
        public array $user,
        public array $team,
        public array $subscription,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            user        : [
                'viewAny' => $user->can(Permission::UserViewAny->value),
                'view'    => $user->can(Permission::UserView->value),
                'create'  => $user->can(Permission::UserCreate->value),
                'update'  => $user->can(Permission::UserUpdate->value),
                'delete'  => $user->can(Permission::UserDelete->value),
            ],
            team        : [
                'view'   => $user->can(Permission::TeamView->value),
                'update' => $user->can(Permission::TeamUpdate->value),
                'delete' => $user->can(Permission::TeamDelete->value),
            ],
            subscription: [
                'view'   => $user->can(Permission::SubscriptionView->value),
                'update' => $user->can(Permission::SubscriptionUpdate->value),
            ],
        );
    }
}
