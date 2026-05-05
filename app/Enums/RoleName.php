<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super-admin';
    case Tester     = 'tester';
    case Owner      = 'owner';
    case Admin      = 'admin';
    case Member     = 'member';

    /** @return array<PermissionName> */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => [
                PermissionName::Admin,
            ],
            self::Tester => [
                PermissionName::Test,
            ],
            self::Owner => [
                PermissionName::UserViewAny,
                PermissionName::UserView,
                PermissionName::UserCreate,
                PermissionName::UserUpdate,
                PermissionName::UserDelete,
                PermissionName::TeamView,
                PermissionName::TeamUpdate,
                PermissionName::TeamDelete,
            ],
            self::Admin => [
                PermissionName::UserViewAny,
                PermissionName::UserView,
                PermissionName::UserCreate,
                PermissionName::UserUpdate,
                PermissionName::UserDelete,
            ],
            self::Member => [
                PermissionName::UserViewAny,
                PermissionName::UserView,
                PermissionName::TeamView,
            ],
        };
    }

    public function label(): string
    {
        return __("roles.{$this->value}");
    }
}
