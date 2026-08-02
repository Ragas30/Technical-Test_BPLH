<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\IndexProjectRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    private const PROJECT_EXAMPLE = [
        'id' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        'project_number' => 'PRJ-2026-00001',
        'slug' => 'pembangunan-ipal-kawasan-industri',
        'title' => 'Pembangunan Instalasi Pengolahan Air Limbah Kawasan Industri',
        'description' => 'Pembangunan IPAL komunal untuk kawasan industri dengan kapasitas 100 m3/hari.',
        'status' => 'submitted',
        'submitted_at' => '2026-08-01T09:30:00+07:00',
        'user' => [
            'id' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
            'name' => 'Budi Santoso',
            'email' => 'budi@docflow.test',
        ],
        'reviews' => [
            [
                'id' => '7c1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
                'status' => 'under_review',
                'notes' => null,
                'reviewed_at' => null,
                'created_at' => '2026-08-01T10:00:00+07:00',
                'reviewer' => ['id' => '5f1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d', 'name' => 'Reviewer BPLH'],
                'logs' => [
                    [
                        'id' => '3a1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
                        'action' => 'under_review',
                        'notes' => null,
                        'created_at' => '2026-08-01T10:00:00+07:00',
                        'reviewer' => ['id' => '5f1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d', 'name' => 'Reviewer BPLH'],
                    ],
                ],
            ],
        ],
        'latest_review' => [
            'id' => '7c1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
            'status' => 'under_review',
            'notes' => null,
            'reviewed_at' => null,
            'created_at' => '2026-08-01T10:00:00+07:00',
            'reviewer' => ['id' => '5f1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d', 'name' => 'Reviewer BPLH'],
            'logs' => [],
        ],
        'created_at' => '2026-08-01T09:00:00+07:00',
        'updated_at' => '2026-08-01T10:00:00+07:00',
        'deleted_at' => null,
    ];

    private const PROJECT_PAGE_EXAMPLE = [
        'data' => [self::PROJECT_EXAMPLE],
        'links' => [
            'first' => 'http://localhost:8000/api/projects?page=1',
            'last' => 'http://localhost:8000/api/projects?page=2',
            'prev' => null,
            'next' => 'http://localhost:8000/api/projects?page=2',
        ],
        'meta' => [
            'current_page' => 1,
            'from' => 1,
            'last_page' => 2,
            'path' => 'http://localhost:8000/api/projects',
            'per_page' => 15,
            'to' => 15,
            'total' => 19,
        ],
    ];

    public function __construct(private readonly ProjectService $projectService) {}

    #[Response(200, 'Daftar project dengan pagination.', examples: [self::PROJECT_PAGE_EXAMPLE])]
    public function index(IndexProjectRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        return ProjectResource::collection($this->projectService->paginate($request->toDTO()));
    }

    #[Response(200, 'Daftar project milik pengguna yang sedang login.', examples: [self::PROJECT_PAGE_EXAMPLE])]
    public function mine(IndexProjectRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewOwn', Project::class);

        return ProjectResource::collection($this->projectService->paginate($request->toDTO(), $request->user()->id));
    }

    #[Response(200, 'Detail project.', examples: [['data' => self::PROJECT_EXAMPLE]])]
    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return ProjectResource::make($this->projectService->find($project->id));
    }

    #[Response(201, 'Project berhasil dibuat dengan status draft.', examples: [['data' => self::PROJECT_EXAMPLE]])]
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        return ProjectResource::make($this->projectService->create($request->toDTO(), $request->user()))
            ->response()
            ->setStatusCode(201);
    }

    #[Response(200, 'Project berhasil diperbarui.', examples: [['data' => self::PROJECT_EXAMPLE]])]
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        return ProjectResource::make($this->projectService->update($project->id, $request->toDTO()));
    }

    #[Response(200, 'Project berhasil dihapus (soft delete).', examples: [['message' => 'Project berhasil dihapus.']])]
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projectService->delete($project->id);

        return response()->json(['message' => 'Project berhasil dihapus.']);
    }

    #[Response(200, 'Project berhasil diajukan untuk direview.', examples: [['data' => self::PROJECT_EXAMPLE]])]
    public function submit(Project $project): ProjectResource
    {
        $this->authorize('submit', $project);

        return ProjectResource::make($this->projectService->submit($project->id));
    }
}
