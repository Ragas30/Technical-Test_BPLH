<?php

namespace App\Services;

use App\DTO\ActivityLog\ActivityLogQueryDTO;
use App\Enums\ActivityAction;
use App\Models\Project;
use App\Models\User;
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

    public function record(ActivityAction $action, string $description, ?User $user = null, ?Project $project = null): void
    {
        $this->activityLogRepository->create([
            'user_id' => $user?->id ?? auth()->id(),
            'project_id' => $project?->id,
            'action' => $action,
            'description' => $description,
            'properties' => $project !== null
                ? ['project_number' => $project->project_number]
                : ($user !== null ? ['email' => $user->email] : []),
        ]);
    }
}
