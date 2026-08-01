<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Enums\ReviewStatus;
use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Review;
use App\Models\User;
use App\Repositories\ActivityLogRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ReviewRepository $reviewRepository,
        private readonly ActivityLogRepository $activityLogRepository,
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $user): array
    {
        if ($user->hasRole(Role::Admin)) {
            return $this->adminDashboard();
        }

        if ($user->hasRole(Role::Reviewer)) {
            return $this->reviewerDashboard($user->id);
        }

        return $this->applicantDashboard($user->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminDashboard(): array
    {
        $statusCounts = $this->projectRepository->statusCounts();

        return [
            'role' => Role::Admin->value,
            'statistics' => [
                'total_users' => $this->userRepository->count(),
                'total_projects' => array_sum($statusCounts),
                ...$this->statusTotals($statusCounts),
            ],
            'monthly_stats' => $this->projectRepository->monthlyStats(),
            'recent_activities' => $this->formatActivities($this->activityLogRepository->latest()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewerDashboard(string $reviewerId): array
    {
        $statusCounts = $this->reviewRepository->statusCounts($reviewerId);

        return [
            'role' => Role::Reviewer->value,
            'statistics' => [
                'total_reviews' => array_sum($statusCounts),
                ...collect(ReviewStatus::cases())->mapWithKeys(
                    fn (ReviewStatus $status) => [$status->value => $statusCounts[$status->value] ?? 0]
                )->all(),
            ],
            'monthly_stats' => $this->reviewRepository->monthlyStats($reviewerId),
            'recent_reviews' => $this->formatReviews($this->reviewRepository->latest($reviewerId)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function applicantDashboard(string $userId): array
    {
        $statusCounts = $this->projectRepository->statusCounts($userId);

        return [
            'role' => Role::Applicant->value,
            'statistics' => [
                'total_projects' => array_sum($statusCounts),
                ...$this->statusTotals($statusCounts),
            ],
            'monthly_stats' => $this->projectRepository->monthlyStats($userId),
            'recent_activities' => $this->formatActivities($this->activityLogRepository->latest($userId)),
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, int>
     */
    private function statusTotals(array $counts): array
    {
        return collect(ProjectStatus::cases())->mapWithKeys(
            fn (ProjectStatus $status) => [$status->value => $counts[$status->value] ?? 0]
        )->all();
    }

    /**
     * @param  Collection<int, ActivityLog>  $logs
     * @return array<int, array<string, mixed>>
     */
    private function formatActivities(Collection $logs): array
    {
        return $logs
            ->map(fn (ActivityLog $log): array => [
                'id' => $log->id,
                'user' => $log->user?->name ?? 'Sistem',
                'action' => $log->action->value,
                'description' => $log->description,
                'created_at' => $log->created_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Review>  $reviews
     * @return array<int, array<string, mixed>>
     */
    private function formatReviews(Collection $reviews): array
    {
        return $reviews
            ->map(fn (Review $review): array => [
                'id' => $review->id,
                'project_title' => $review->project?->title,
                'project_number' => $review->project?->project_number,
                'status' => $review->status->value,
                'notes' => $review->notes,
                'reviewed_at' => $review->reviewed_at?->toISOString(),
                'created_at' => $review->created_at?->toISOString(),
            ])
            ->values()
            ->all();
    }
}
