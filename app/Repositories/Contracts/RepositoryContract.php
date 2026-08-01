<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryContract
{
    public function all(array $columns = ['*']): Collection;

    public function paginate(int $perPage = 15, array $columns = ['*']);

    public function find(int|string $id, array $columns = ['*']): ?Model;

    public function findOrFail(int|string $id, array $columns = ['*']): Model;

    public function findWhere(string $column, mixed $value): Collection;

    public function findWhereFirst(string $column, mixed $value): ?Model;

    public function findWhereIn(string $column, array $values): Collection;

    public function create(array $attributes): Model;

    public function update(int|string $id, array $attributes): Model;

    public function updateOrCreate(array $attributes, array $values = []): Model;

    public function delete(int|string $id): bool;
}
