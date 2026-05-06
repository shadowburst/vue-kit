<?php

declare(strict_types=1);

namespace App\Enums\Role;

use App\Enums\Permission\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

enum Role: string
{
    case SuperAdmin = 'super-admin';
    case Tester     = 'tester';
    case Owner      = 'owner';
    case Admin      = 'admin';
    case Member     = 'member';

    /** @return array<Permission> */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => [
                Permission::Admin,
            ],
            self::Tester => [
                Permission::Test,
            ],
            self::Owner => [
                Permission::UserViewAny,
                Permission::UserView,
                Permission::UserCreate,
                Permission::UserUpdate,
                Permission::UserDelete,
                Permission::TeamView,
                Permission::TeamUpdate,
                Permission::TeamDelete,
            ],
            self::Admin => [
                Permission::UserViewAny,
                Permission::UserView,
                Permission::UserCreate,
                Permission::UserUpdate,
                Permission::UserDelete,
            ],
            self::Member => [
                Permission::UserViewAny,
                Permission::UserView,
                Permission::TeamView,
            ],
        };
    }

    public function label(): string
    {
        $translation = __("roles.{$this->value}");

        return is_string($translation) ? $translation : $this->value;
    }

    public function model(): SpatieRole
    {
        $cache = &self::modelCache();
        $key   = "{$this->value}:".(\getPermissionsTeamId() ?? '');

        return $cache[$key] ??= SpatieRole::findByName($this->value);
    }

    public static function flushModelCache(): void
    {
        $cache = &self::modelCache();
        $cache = [];
    }

    /** @return array<string, SpatieRole> */
    private static function &modelCache(): array
    {
        /** @var array<string, SpatieRole> $cache */
        static $cache = [];

        return $cache;
    }
}
