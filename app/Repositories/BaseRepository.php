<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository implements RepositoryContract
{
    protected Model $model;

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*'])
    {
        return $this->model->select($columns)->paginate($perPage);
    }

    public function find(int|string $id, array $columns = ['*']): ?Model
    {
        return $this->model->find($id, $columns);
    }

    public function findOrFail(int|string $id, array $columns = ['*']): Model
    {
        return $this->model->findOrFail($id, $columns);
    }

    public function findWhere(string $column, mixed $value): Collection
    {
        return $this->model->where($column, $value)->get();
    }

    public function findWhereFirst(string $column, mixed $value): ?Model
    {
        return $this->model->where($column, $value)->first();
    }

    public function findWhereIn(string $column, array $values): Collection
    {
        return $this->model->whereIn($column, $values)->get();
    }

    public function create(array $attributes): Model
    {
        return $this->model->create($attributes);
    }

    public function update(int|string $id, array $attributes): Model
    {
        $model = $this->findOrFail($id);

        $model->update($attributes);

        return $model;
    }

    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    public function delete(int|string $id): bool
    {
        return (bool) $this->findOrFail($id)->delete();
    }

    protected function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM')",
            'mysql' => "DATE_FORMAT({$column}, '%Y-%m')",
            default => "strftime('%Y-%m', {$column})",
        };
    }

    protected function likePattern(string $search): string
    {
        return '%'.addcslashes(mb_strtolower($search), '%_\\').'%';
    }

    /**
     * @return array<string, int>
     */
    protected function statusCountsFor(string $statusColumn = 'status', ?string $scopedColumn = null, ?string $scopedValue = null): array
    {
        return $this->model->query()
            ->when($scopedColumn !== null && $scopedValue !== null, fn ($query) => $query->where($scopedColumn, $scopedValue))
            ->selectRaw("{$statusColumn}, COUNT(*) as total")
            ->groupBy($statusColumn)
            ->pluck('total', $statusColumn)
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * @return array<int, array{month: string, total: int}>
     */
    protected function monthlyStatsFor(?string $scopedColumn = null, ?string $scopedValue = null, int $months = 6): array
    {
        $counts = $this->model->query()
            ->when($scopedColumn !== null && $scopedValue !== null, fn ($query) => $query->where($scopedColumn, $scopedValue))
            ->where('created_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->selectRaw($this->monthExpression('created_at').' as month, COUNT(*) as total')
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
