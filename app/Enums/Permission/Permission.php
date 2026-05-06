<?php

declare(strict_types=1);

namespace App\Enums\Permission;

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

        // Per-request cache only; cross-request would need invalidation on permission changes.
        return Cache::driver('array')
            ->tags(['enum.permission'])
            ->rememberForever($key, fn (): SpatiePermission => SpatiePermission::findByName($this->value));
    }

    public static function flushModelCache(): void
    {
        Cache::driver('array')->tags(['enum.permission'])->flush();
    }
}
