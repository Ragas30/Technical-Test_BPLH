<?php

namespace App\Repositories;

use App\Models\ProjectDocument;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class DocumentRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new ProjectDocument;
    }

    public function forProject(string $projectId): Collection
    {
        return $this->model->query()
            ->where('project_id', $projectId)
            ->with('uploader:id,name,email')
            ->latest('created_at')
            ->get();
    }

    /**
     * @param  array<int, array<string, mixed>>  $attributes
     */
    public function insertMany(array $attributes): void
    {
        $now = now();

        foreach ($attributes as &$row) {
            $row['id'] ??= (string) Str::uuid();
            $row['created_at'] ??= $now;
            $row['updated_at'] ??= $now;
        }
        unset($row);

        $this->model->newQuery()->insert($attributes);
    }

    /**
     * @param  array<int, string>  $ids
     */
    public function findManyWithUploader(array $ids): Collection
    {
        return $this->model->query()
            ->whereIn('id', $ids)
            ->with('uploader:id,name,email')
            ->get();
    }
}
