<?php

namespace App\Repositories;

use App\DTO\Review\ReviewQueryDTO;
use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ReviewRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new Review;
    }

    public function hasActiveReview(string $projectId): bool
    {
        return $this->model->query()
            ->where('project_id', $projectId)
            ->whereIn('status', [ReviewStatus::Pending->value, ReviewStatus::UnderReview->value])
            ->exists();
    }

    public function paginateWithFilters(ReviewQueryDTO $dto, ?string $reviewerId = null): LengthAwarePaginator
    {
        return $this->model->query()
            ->with(['project:id,title,project_number,status', 'reviewer:id,name,email'])
            ->when($reviewerId !== null, fn ($query) => $query->where('reviewer_id', $reviewerId))
            ->when($dto->search !== null, fn ($query) => $query->whereHas('project', function ($query) use ($dto): void {
                $pattern = '%'.addcslashes(mb_strtolower($dto->search), '%_\\').'%';

                $query->whereRaw('LOWER(title) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(project_number) LIKE ?', [$pattern]);
            }))
            ->when($dto->status !== null, fn ($query) => $query->where('status', $dto->status))
            ->orderBy($dto->sortBy, $dto->sortDir)
            ->paginate($dto->perPage);
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(?string $reviewerId = null): array
    {
        return $this->model->query()
            ->when($reviewerId !== null, fn ($query) => $query->where('reviewer_id', $reviewerId))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * @return array<int, array{month: string, total: int}>
     */
    public function monthlyStats(?string $reviewerId = null, int $months = 6): array
    {
        $counts = $this->model->query()
            ->when($reviewerId !== null, fn ($query) => $query->where('reviewer_id', $reviewerId))
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

    public function latest(?string $reviewerId = null, int $limit = 10): Collection
    {
        return $this->model->query()
            ->with('project:id,title,project_number')
            ->when($reviewerId !== null, fn ($query) => $query->where('reviewer_id', $reviewerId))
            ->latest()
            ->limit($limit)
            ->get();
    }
}
