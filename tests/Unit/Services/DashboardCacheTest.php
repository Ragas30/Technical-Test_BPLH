<?php

namespace Tests\Unit\Services;

use App\Enums\Role;
use App\Services\DashboardCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();
    }

    public function test_remember_uses_stable_key_for_same_role_and_user(): void
    {
        $calls = 0;

        $first = DashboardCache::remember(Role::Applicant, 'user-1', function () use (&$calls): array {
            $calls++;

            return ['total' => 3];
        });

        $second = DashboardCache::remember(Role::Applicant, 'user-1', function () use (&$calls): array {
            $calls++;

            return ['total' => 99];
        });

        $this->assertSame(['total' => 3], $first);
        $this->assertSame(['total' => 3], $second);
        $this->assertSame(1, $calls);
    }

    public function test_invalidate_bumps_version_and_forces_recompute(): void
    {
        DashboardCache::remember(Role::Reviewer, 'reviewer-1', fn (): array => ['pending' => 5]);
        $initialKey = DashboardCache::key(Role::Reviewer, 'reviewer-1');

        DashboardCache::invalidate();

        $nextKey = DashboardCache::key(Role::Reviewer, 'reviewer-1');
        $calls = 0;

        $payload = DashboardCache::remember(Role::Reviewer, 'reviewer-1', function () use (&$calls): array {
            $calls++;

            return ['pending' => 2];
        });

        $this->assertNotSame($initialKey, $nextKey);
        $this->assertSame(['pending' => 2], $payload);
        $this->assertSame(1, $calls);
    }
}
