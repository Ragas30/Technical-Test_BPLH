<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\DashboardCache;

class ProjectObserver
{
    public function created(Project $project): void
    {
        DashboardCache::invalidate();
    }

    public function updated(Project $project): void
    {
        DashboardCache::invalidate();
    }

    public function deleted(Project $project): void
    {
        DashboardCache::invalidate();
    }

    public function restored(Project $project): void
    {
        DashboardCache::invalidate();
    }
}
