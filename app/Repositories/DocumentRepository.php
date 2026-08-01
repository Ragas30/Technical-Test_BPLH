<?php

namespace App\Repositories;

use App\Models\ProjectDocument;
use Illuminate\Database\Eloquent\Collection;

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
}
