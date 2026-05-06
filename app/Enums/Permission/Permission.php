<?php

declare(strict_types=1);

namespace App\Enums\Permission;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
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
        $key = "enum.permission.{$this->value}";

        /** @var CacheRepository $cache */
        $cache = Cache::driver('array');

        // Per-request cache only; cross-request would need invalidation on permission changes.
        $model = $cache->tags(['enum.permission'])
            ->rememberForever($key, fn (): SpatiePermission => SpatiePermission::findByName($this->value));

        \assert($model instanceof SpatiePermission, 'rememberForever returns the resolved permission model');

        return $model;
    }

    public static function flushModelCache(): void
    {
        /** @var CacheRepository $cache */
        $cache = Cache::driver('array');
        $cache->tags(['enum.permission'])->flush();
    }
}
