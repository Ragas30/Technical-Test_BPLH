<?php

namespace App\Repositories;

use App\Models\ReviewLog;

class ReviewLogRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new ReviewLog;
    }
}
