<?php

namespace App\Repositories;

use App\DTO\Review\ReviewQueryDTO;
use App\Enums\ReviewStatus;
use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ReviewRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new Review;
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
     * @param  callable(Collection<int, Review>): void  $callback
     */
    public function chunkFiltered(?string $search, ?string $status, int $size, callable $callback): void
    {
        $this->filteredQuery($search, $status)->chunk($size, $callback);
    }

    private function filteredQuery(?string $search = null, ?string $status = null): Builder
    {
        return $this->model->query()
            ->with(['project:id,title,project_number,status', 'reviewer:id,name,email'])
            ->when($search !== null, fn ($query) => $query->whereHas('project', function ($query) use ($search): void {
                $pattern = $this->likePattern($search);

                $query->whereRaw('LOWER(title) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(project_number) LIKE ?', [$pattern]);
            }))
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderBy('created_at', 'desc');
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
                $pattern = $this->likePattern($dto->search);

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
        return $this->statusCountsFor('status', 'reviewer_id', $reviewerId);
    }

    /**
     * @return array<int, array{month: string, total: int}>
     */
    public function monthlyStats(?string $reviewerId = null, int $months = 6): array
    {
        return $this->monthlyStatsFor('reviewer_id', $reviewerId, $months);
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
