<?php

namespace App\Observers;

use App\Models\Review;
use App\Services\DashboardCache;

class ReviewObserver
{
    public function created(Review $review): void
    {
        DashboardCache::invalidate();
    }

    public function updated(Review $review): void
    {
        DashboardCache::invalidate();
    }
}
