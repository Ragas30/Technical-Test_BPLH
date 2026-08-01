<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\IndexProjectRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projectService) {}

    public function index(IndexProjectRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        return ProjectResource::collection($this->projectService->paginate($request->toDTO()));
    }

    public function mine(IndexProjectRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewOwn', Project::class);

        return ProjectResource::collection($this->projectService->paginate($request->toDTO(), $request->user()->id));
    }

    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return ProjectResource::make($this->projectService->find($project->id));
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        return ProjectResource::make($this->projectService->create($request->toDTO(), $request->user()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        return ProjectResource::make($this->projectService->update($project->id, $request->toDTO()));
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projectService->delete($project->id);

        return response()->json(['message' => 'Project berhasil dihapus.']);
    }

    public function submit(Project $project): ProjectResource
    {
        $this->authorize('submit', $project);

        return ProjectResource::make($this->projectService->submit($project->id));
    }
}
