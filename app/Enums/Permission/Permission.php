<?php

declare(strict_types=1);

namespace App\Enums\Permission;

use Spatie\Permission\Models\Permission as SpatiePermission;

enum Permission: string
{
    case Admin       = 'admin';
    case Test        = 'test';
    case UserViewAny = 'user.viewAny';
    case UserView    = 'user.view';
    case UserCreate  = 'user.create';
    case UserUpdate  = 'user.update';
    case UserDelete  = 'user.delete';
    case TeamView    = 'team.view';
    case TeamUpdate  = 'team.update';
    case TeamDelete  = 'team.delete';

    public function model(): SpatiePermission
    {
        $cache = &self::modelCache();

        return $cache[$this->value] ??= SpatiePermission::findByName($this->value);
    }

    public static function flushModelCache(): void
    {
        $cache = &self::modelCache();
        $cache = [];
    }

    /** @return array<string, SpatiePermission> */
    private static function &modelCache(): array
    {
        /** @var array<string, SpatiePermission> $cache */
        static $cache = [];

        return $cache;
    }
}
