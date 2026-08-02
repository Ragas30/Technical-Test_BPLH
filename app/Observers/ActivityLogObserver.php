<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Services\DashboardCache;

class ActivityLogObserver
{
    public function created(ActivityLog $activityLog): void
    {
        DashboardCache::invalidate();
    }
}
