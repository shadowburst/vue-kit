<?php

declare(strict_types=1);

namespace App\Enums\Role;

use App\Enums\Permission\Permission;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role as SpatieRole;

enum Role: string
{
    case SuperAdmin = 'super-admin';
    case Tester     = 'tester';
    case Manager    = 'manager';
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
            self::Manager => [
                Permission::UserViewAny,
                Permission::UserView,
                Permission::UserCreate,
                Permission::UserUpdate,
                Permission::UserDelete,
                Permission::TeamView,
                Permission::SubscriptionView,
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
        $key = "enum.role.{$this->value}.team.".(\getPermissionsTeamId() ?? 'null');

        /** @var CacheRepository $cache */
        $cache = Cache::driver('array');

        // Per-request cache only; cross-request would need invalidation on role changes.
        return $cache->tags(['enum.role'])
            ->rememberForever($key, fn (): SpatieRole => SpatieRole::findByName($this->value));
    }

    public static function flushModelCache(): void
    {
        /** @var CacheRepository $cache */
        $cache = Cache::driver('array');
        $cache->tags(['enum.role'])->flush();
    }
}
