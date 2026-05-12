<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Enums\Permission\Permission;
use App\Models\Team;
use App\Models\User;
use App\Models\Subscription;
use Spatie\LaravelData\Data;

final class AuthAbilitiesData extends Data
{
    public function __construct(
        /** @var array{view_any: bool, view: bool, create: bool, update: bool, delete: bool} */
        public array $user,
        /** @var array{view: bool, update: bool, delete: bool} */
        public array $team,
        /** @var array{view: bool, create: bool, update: bool, cancel: bool, resume: bool} */
        public array $subscription,
    ) {}

    public static function fromUser(?User $user = null, ?Team $team = null): self
    {
        return new self(
            user        : self::userAbilities($user),
            team        : self::teamAbilities($user, $team),
            subscription: self::subscriptionAbilities($user, $team),
        );
    }

    /**
     * @return array{view_any: bool, view: bool, create: bool, update: bool, delete: bool}
     */
    private static function userAbilities(?User $user): array
    {
        return [
            'view_any' => self::can($user, Permission::UserViewAny->value),
            'view'     => self::can($user, Permission::UserView->value),
            'create'   => self::can($user, 'create', User::class),
            'update'   => self::can($user, Permission::UserUpdate->value),
            'delete'   => self::can($user, Permission::UserDelete->value),
        ];
    }

    /**
     * @return array{view: bool, update: bool, delete: bool}
     */
    private static function teamAbilities(?User $user, ?Team $team): array
    {
        if ($team === null) {
            return ['view' => false, 'update' => false, 'delete' => false];
        }

        return [
            'view'   => self::can($user, Permission::TeamView->value, $team),
            'update' => self::can($user, 'update', $team),
            'delete' => self::can($user, 'delete', $team),
        ];
    }

    /**
     * @return array{view: bool, create: bool, update: bool, cancel: bool, resume: bool}
     */
    private static function subscriptionAbilities(?User $user, ?Team $team): array
    {
        $view = self::can($user, Permission::SubscriptionView->value);

        if ($team === null) {
            return ['view' => $view, 'create' => false, 'update' => false, 'cancel' => false, 'resume' => false];
        }

        return [
            'view'   => $view,
            'create' => self::can($user, 'create', [Subscription::class, $team]),
            'update' => self::can($user, 'update', [Subscription::class, $team]),
            'cancel' => self::can($user, 'cancel', [Subscription::class, $team]),
            'resume' => self::can($user, 'resume', [Subscription::class, $team]),
        ];
    }

    private static function can(?User $user, string $ability, mixed $arguments = null): bool
    {
        if ($user === null) {
            return false;
        }

        return $arguments === null ? $user->can($ability) : $user->can($ability, $arguments);
    }
}
