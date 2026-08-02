<?php

namespace App\Repositories;

use App\DTO\ActivityLog\ActivityLogQueryDTO;
use App\Models\ActivityLog;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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

    public function paginateWithFilters(ActivityLogQueryDTO $dto, ?string $userId = null): LengthAwarePaginator
    {
        return $this->baseQuery($dto, $userId)->paginate($dto->perPage);
    }

    public function cursorPaginateWithFilters(ActivityLogQueryDTO $dto, ?string $userId = null): CursorPaginator
    {
        return $this->baseQuery($dto, $userId)->cursorPaginate($dto->perPage);
    }

    private function baseQuery(ActivityLogQueryDTO $dto, ?string $userId = null): Builder
    {
        return $this->model->query()
            ->with(['user:id,name', 'project:id,project_number,title'])
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->when($dto->search !== null, fn ($query) => $query->where(function ($query) use ($dto): void {
                $pattern = $this->likePattern($dto->search);

                $query->whereRaw('LOWER(description) LIKE ?', [$pattern])
                    ->orWhereHas('user', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', [$pattern]));
            }))
            ->when($dto->action !== null, fn ($query) => $query->where('action', $dto->action))
            ->latest('created_at');
    }
}
