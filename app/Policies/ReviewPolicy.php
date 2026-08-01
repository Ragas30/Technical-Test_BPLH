<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::ReviewViewAny);
    }

    public function view(User $user, Review $review): bool
    {
        return $user->hasPermissionTo(Permission::ReviewView);
    }

    public function start(User $user, Project $project): bool
    {
        return $user->hasPermissionTo(Permission::ReviewStart)
            && $project->status === ProjectStatus::Submitted;
    }

    public function decide(User $user, Review $review): bool
    {
        return $review->reviewer_id === $user->id;
    }

    public function comment(User $user, Review $review): bool
    {
        return $review->reviewer_id === $user->id;
    }
}
