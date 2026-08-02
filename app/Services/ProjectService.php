<?php

namespace App\Services;

use App\DTO\Project\CreateProjectDTO;
use App\DTO\Project\ProjectQueryDTO;
use App\DTO\Project\UpdateProjectDTO;
use App\Enums\ActivityAction;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectSubmittedNotification;
use App\Repositories\ProjectRepository;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ActivityLogService $activityLogService,
        private readonly UserRepository $userRepository,
    ) {}

    public function paginate(ProjectQueryDTO $dto, ?string $userId = null): LengthAwarePaginator
    {
        return $this->projectRepository->paginateWithFilters($dto, $userId);
    }

    public function find(string $id): Project
    {
        return $this->projectRepository->findOrFail($id)->load([
            'user:id,name,email',
            'reviews' => fn ($query) => $query->orderByDesc('created_at'),
            'reviews.reviewer:id,name,email',
            'reviews.logs' => fn ($query) => $query->orderByDesc('created_at'),
            'reviews.logs.reviewer:id,name,email',
        ]);
    }

    public function create(CreateProjectDTO $dto, User $user): Project
    {
        return DB::transaction(function () use ($dto, $user): Project {
            $project = $this->projectRepository->create([
                'user_id' => $user->id,
                'project_number' => $this->projectRepository->nextProjectNumber(),
                'slug' => Str::slug($dto->title).'-'.Str::lower(Str::random(6)),
                'title' => $dto->title,
                'description' => $dto->description,
                'status' => ProjectStatus::Draft,
                'submitted_at' => null,
            ]);

            $this->activityLogService->record(ActivityAction::ProjectCreated, 'Project '.$project->title.' dibuat.', project: $project);

            return $project->load('user:id,name,email');
        });
    }

    public function update(string $id, UpdateProjectDTO $dto): Project
    {
        return DB::transaction(function () use ($id, $dto): Project {
            $project = $this->projectRepository->findOrFail($id);

            $this->ensureDraft($project, 'Project yang sudah diajukan tidak dapat diubah.');

            $attributes = [
                'title' => $dto->title,
                'description' => $dto->description,
            ];

            if ($dto->title !== $project->title) {
                $attributes['slug'] = Str::slug($dto->title).'-'.Str::lower(Str::random(6));
            }

            $this->projectRepository->update($id, $attributes);
            $project->refresh();

            $this->activityLogService->record(ActivityAction::ProjectUpdated, 'Project '.$project->title.' diperbarui.', project: $project);

            return $project->load('user:id,name,email');
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $project = $this->projectRepository->findOrFail($id);

            $this->ensureDraft($project, 'Project yang sudah diajukan tidak dapat dihapus.');

            $this->projectRepository->delete($id);

            $this->activityLogService->record(ActivityAction::ProjectDeleted, 'Project '.$project->title.' dihapus.', project: $project);
        });
    }

    public function submit(string $id): Project
    {
        $project = DB::transaction(function () use ($id): Project {
            $project = $this->projectRepository->findOrFail($id);

            $this->ensureSubmittable($project);

            $project = $this->projectRepository->update($id, [
                'status' => ProjectStatus::Submitted,
                'submitted_at' => now(),
            ]);

            $this->activityLogService->record(ActivityAction::ProjectSubmitted, 'Project '.$project->title.' diajukan untuk review.', project: $project);

            return $project->load('user:id,name,email');
        });

        Notification::send($this->userRepository->reviewers(), new ProjectSubmittedNotification($project));

        return $project;
    }

    private function ensureDraft(Project $project, string $message): void
    {
        if ($project->status !== ProjectStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => [$message],
            ]);
        }
    }

    private function ensureSubmittable(Project $project): void
    {
        if (! in_array($project->status, [ProjectStatus::Draft, ProjectStatus::Revision], true)) {
            throw ValidationException::withMessages([
                'status' => ['Project dengan status ini tidak dapat diajukan.'],
            ]);
        }
    }
}
