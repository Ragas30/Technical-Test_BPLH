<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $this->isProjectOwner($user, $project) || $user->hasRole(Role::Reviewer);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->isProjectOwner($user, $project);
    }

    public function update(User $user, ProjectDocument $document): bool
    {
        return $this->isProjectOwner($user, $document->project);
    }

    public function delete(User $user, ProjectDocument $document): bool
    {
        return $this->isProjectOwner($user, $document->project);
    }

    public function view(User $user, ProjectDocument $document): bool
    {
        return $this->isProjectOwner($user, $document->project) || $user->hasRole(Role::Reviewer);
    }

    private function isProjectOwner(User $user, Project $project): bool
    {
        return $user->id === $project->user_id || $user->hasRole(Role::Admin);
    }
}
