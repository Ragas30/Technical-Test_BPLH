<?php

namespace App\Observers;

use App\Models\User;
use App\Services\DashboardCache;

class UserObserver
{
    public function created(User $user): void
    {
        DashboardCache::invalidate();
    }

    public function updated(User $user): void
    {
        DashboardCache::invalidate();
    }

    public function deleted(User $user): void
    {
        DashboardCache::invalidate();
    }

    public function restored(User $user): void
    {
        DashboardCache::invalidate();
    }
}
