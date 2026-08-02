<?php

namespace App\Repositories;

use App\DTO\Project\ProjectQueryDTO;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new Project;
    }

    public function filtered(?string $search = null, ?string $status = null): Collection
    {
        return $this->model->query()
            ->with('user:id,name,email')
            ->when($search !== null, fn ($query) => $query->where(function ($query) use ($search): void {
                $pattern = '%'.addcslashes(mb_strtolower($search), '%_\\').'%';

                $query->whereRaw('LOWER(title) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(project_number) LIKE ?', [$pattern]);
            }))
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * @param  callable(Collection<int, Project>): void  $callback
     */
    public function chunkFiltered(?string $search, ?string $status, int $size, callable $callback): void
    {
        $this->model->query()
            ->with('user:id,name,email')
            ->when($search !== null, fn ($query) => $query->where(function ($query) use ($search): void {
                $pattern = '%'.addcslashes(mb_strtolower($search), '%_\\').'%';

                $query->whereRaw('LOWER(title) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(project_number) LIKE ?', [$pattern]);
            }))
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->chunk($size, $callback);
    }

    public function paginateWithFilters(ProjectQueryDTO $dto, ?string $userId = null): LengthAwarePaginator
    {
        return $this->model->query()
            ->with('user:id,name,email')
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->when($dto->search !== null, fn ($query) => $query->where(function ($query) use ($dto): void {
                $pattern = '%'.addcslashes(mb_strtolower($dto->search), '%_\\').'%';

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
        return $this->model->query()
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * @return array<int, array{month: string, total: int}>
     */
    public function monthlyStats(?string $userId = null, int $months = 6): array
    {
        $counts = $this->model->query()
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->where('created_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as month, COUNT(*) as total")
            ->groupBy('month')
            ->pluck('total', 'month')
            ->map(fn ($total) => (int) $total)
            ->all();

        $result = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $result[] = ['month' => $month, 'total' => $counts[$month] ?? 0];
        }

        return $result;
    }
}
