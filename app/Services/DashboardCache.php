<?php

namespace App\Services;

use App\Enums\Role;
use Illuminate\Support\Facades\Cache;

final class DashboardCache
{
    private const VERSION_KEY = 'dashboard:version';

    private const TTL = 300;

    public static function key(Role $role, ?string $userId = null): string
    {
        $version = (int) Cache::get(self::VERSION_KEY, 0);

        return 'dashboard:v'.$version.':'.$role->value.($userId !== null ? ':'.$userId : '');
    }

    public static function remember(Role $role, ?string $userId, callable $resolver): array
    {
        return Cache::remember(self::key($role, $userId), self::TTL, $resolver);
    }

    public static function invalidate(): void
    {
        if (! Cache::has(self::VERSION_KEY)) {
            Cache::put(self::VERSION_KEY, 1, self::TTL);

            return;
        }

        Cache::increment(self::VERSION_KEY);
    }
}
