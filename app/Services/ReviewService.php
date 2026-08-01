<?php

namespace App\Services;

use App\DTO\Review\ReviewQueryDTO;
use App\Enums\ActivityAction;
use App\Enums\ProjectStatus;
use App\Enums\ReviewAction;
use App\Enums\ReviewStatus;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use App\Repositories\ActivityLogRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\ReviewLogRepository;
use App\Repositories\ReviewRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
        private readonly ReviewLogRepository $reviewLogRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly ActivityLogRepository $activityLogRepository,
    ) {}

    public function paginate(ReviewQueryDTO $dto, ?string $reviewerId = null): LengthAwarePaginator
    {
        return $this->reviewRepository->paginateWithFilters($dto, $reviewerId);
    }

    public function find(string $id): Review
    {
        return $this->reviewRepository->findOrFail($id)->load([
            'project.user:id,name,email',
            'reviewer:id,name,email',
            'logs.reviewer:id,name,email',
        ]);
    }

    public function start(Project $project, User $reviewer): Review
    {
        return DB::transaction(function () use ($project, $reviewer): Review {
            $this->ensureCanStart($project);

            $review = $this->reviewRepository->create([
                'project_id' => $project->id,
                'reviewer_id' => $reviewer->id,
                'status' => ReviewStatus::UnderReview,
                'notes' => null,
                'reviewed_at' => null,
            ]);

            $this->projectRepository->update($project->id, ['status' => ProjectStatus::UnderReview]);

            $this->logReviewAction($project, $review, $reviewer, ReviewAction::UnderReview, null);
            $this->logActivity($project, ActivityAction::ReviewStarted, 'Review project '.$project->title.' dimulai.');

            return $this->loadDetails($review);
        });
    }

    public function approve(Review $review, User $user, ?string $notes): Review
    {
        return DB::transaction(function () use ($review, $user, $notes): Review {
            $this->ensureUnderReview($review);

            $this->applyDecision(
                $review,
                $user,
                ReviewStatus::Approved,
                ProjectStatus::Approved,
                $notes,
                ReviewAction::Approved,
                ActivityAction::ReviewApproved,
                'Review project '.$review->project->title.' disetujui.',
            );

            return $this->loadDetails($review);
        });
    }

    public function reject(Review $review, User $user, string $notes): Review
    {
        return DB::transaction(function () use ($review, $user, $notes): Review {
            $this->ensureUnderReview($review);
            $this->ensureNotes($notes);

            $this->applyDecision(
                $review,
                $user,
                ReviewStatus::Rejected,
                ProjectStatus::Rejected,
                $notes,
                ReviewAction::Rejected,
                ActivityAction::ReviewRejected,
                'Review project '.$review->project->title.' ditolak.',
            );

            return $this->loadDetails($review);
        });
    }

    public function requestRevision(Review $review, User $user, string $notes): Review
    {
        return DB::transaction(function () use ($review, $user, $notes): Review {
            $this->ensureUnderReview($review);
            $this->ensureNotes($notes);

            $this->applyDecision(
                $review,
                $user,
                ReviewStatus::Revision,
                ProjectStatus::Revision,
                $notes,
                ReviewAction::Revision,
                ActivityAction::RevisionRequested,
                'Revisi diminta untuk project '.$review->project->title.'.',
            );

            return $this->loadDetails($review);
        });
    }

    public function comment(Review $review, User $user, string $notes): Review
    {
        return DB::transaction(function () use ($review, $user, $notes): Review {
            $this->logReviewAction($review->project, $review, $user, ReviewAction::Comment, $notes);

            return $this->loadDetails($review);
        });
    }

    private function applyDecision(
        Review $review,
        User $user,
        ReviewStatus $reviewStatus,
        ProjectStatus $projectStatus,
        ?string $notes,
        ReviewAction $action,
        ActivityAction $activityAction,
        string $description,
    ): void {
        $this->reviewRepository->update($review->id, [
            'status' => $reviewStatus,
            'notes' => $notes,
            'reviewed_at' => now(),
        ]);

        $this->projectRepository->update($review->project_id, ['status' => $projectStatus]);

        $this->logReviewAction($review->project, $review, $user, $action, $notes);
        $this->logActivity($review->project, $activityAction, $description);

        $review->refresh();
    }

    private function ensureCanStart(Project $project): void
    {
        if ($project->status !== ProjectStatus::Submitted) {
            throw ValidationException::withMessages([
                'status' => ['Hanya project dengan status diajukan yang dapat direview.'],
            ]);
        }

        if ($this->reviewRepository->hasActiveReview($project->id)) {
            throw ValidationException::withMessages([
                'status' => ['Project sudah memiliki review yang sedang berjalan.'],
            ]);
        }
    }

    private function ensureUnderReview(Review $review): void
    {
        if ($review->status !== ReviewStatus::UnderReview) {
            throw ValidationException::withMessages([
                'status' => ['Review hanya dapat diputuskan pada status sedang ditinjau.'],
            ]);
        }
    }

    private function ensureNotes(?string $notes): void
    {
        if ($notes === null || trim($notes) === '') {
            throw ValidationException::withMessages([
                'notes' => ['Catatan wajib diisi.'],
            ]);
        }
    }

    private function logReviewAction(Project $project, Review $review, User $user, ReviewAction $action, ?string $notes): void
    {
        $this->reviewLogRepository->create([
            'project_id' => $project->id,
            'review_id' => $review->id,
            'reviewer_id' => $user->id,
            'action' => $action,
            'notes' => $notes,
        ]);
    }

    private function logActivity(Project $project, ActivityAction $action, string $description): void
    {
        $this->activityLogRepository->create([
            'user_id' => auth()->id(),
            'project_id' => $project->id,
            'action' => $action,
            'description' => $description,
            'properties' => ['project_number' => $project->project_number],
        ]);
    }

    private function loadDetails(Review $review): Review
    {
        return $review->load([
            'project:id,title,project_number,status,user_id',
            'reviewer:id,name,email',
            'logs.reviewer:id,name,email',
        ]);
    }
}
