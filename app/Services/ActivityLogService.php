<?php

namespace App\Services;

use App\DTO\ActivityLog\ActivityLogQueryDTO;
use App\Repositories\ActivityLogRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ActivityLogService
{
    public function __construct(private readonly ActivityLogRepository $activityLogRepository) {}

    public function paginate(ActivityLogQueryDTO $dto, ?string $userId = null): LengthAwarePaginator
    {
        return $this->activityLogRepository->paginateWithFilters($dto, $userId);
    }

    public function cursorPaginate(ActivityLogQueryDTO $dto, ?string $userId = null): CursorPaginator
    {
        return $this->activityLogRepository->cursorPaginateWithFilters($dto, $userId);
    }
}
