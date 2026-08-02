<?php

namespace App\Repositories;

use App\DTO\Project\ProjectQueryDTO;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new Project;
    }

    public function filtered(?string $search = null, ?string $status = null): Collection
    {
        return $this->filteredQuery($search, $status)->get();
    }

    public function filteredLimited(int $limit, ?string $search = null, ?string $status = null): Collection
    {
        return $this->filteredQuery($search, $status)->limit($limit)->get();
    }

    /**
     * @param  callable(Collection<int, Project>): void  $callback
     */
    public function chunkFiltered(?string $search, ?string $status, int $size, callable $callback): void
    {
        $this->filteredQuery($search, $status)->chunk($size, $callback);
    }

    private function filteredQuery(?string $search = null, ?string $status = null): Builder
    {
        return $this->model->query()
            ->with('user:id,name,email')
            ->when($search !== null, fn ($query) => $query->where(function ($query) use ($search): void {
                $pattern = $this->likePattern($search);

                $query->whereRaw('LOWER(title) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(project_number) LIKE ?', [$pattern]);
            }))
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderBy('created_at', 'desc');
    }

    public function paginateWithFilters(ProjectQueryDTO $dto, ?string $userId = null): LengthAwarePaginator
    {
        return $this->model->query()
            ->with('user:id,name,email')
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->when($dto->search !== null, fn ($query) => $query->where(function ($query) use ($dto): void {
                $pattern = $this->likePattern($dto->search);

                $query->whereRaw('LOWER(title) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(project_number) LIKE ?', [$pattern]);
            }))
            ->when($dto->status !== null, fn ($query) => $query->where('status', $dto->status))
            ->orderBy($dto->sortBy, $dto->sortDir)
            ->paginate($dto->perPage);
    }

    public function nextProjectNumber(): string
    {
        $year = now()->year;
        $count = $this->model->query()
            ->whereYear('created_at', $year)
            ->withTrashed()
            ->count();

        return 'PRJ-'.$year.'-'.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(?string $userId = null): array
    {
        return $this->statusCountsFor('status', 'user_id', $userId);
    }

    /**
     * @return array<int, array{month: string, total: int}>
     */
    public function monthlyStats(?string $userId = null, int $months = 6): array
    {
        return $this->monthlyStatsFor('user_id', $userId, $months);
    }
}
