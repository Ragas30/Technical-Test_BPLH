<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Collection;

class ActivityLogRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new ActivityLog;
    }

    public function latest(?string $userId = null, int $limit = 10): Collection
    {
        return $this->model->query()
            ->with('user:id,name')
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
