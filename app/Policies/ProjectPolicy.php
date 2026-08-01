<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo(Permission::ProjectViewAny);
    }

    public function viewOwn(User $user): bool
    {
        return $user->checkPermissionTo(Permission::ProjectView);
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo(Permission::ProjectCreate);
    }

    public function view(User $user, Project $project): bool
    {
        return $this->isOwner($user, $project) || $user->checkPermissionTo(Permission::ProjectView);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->isOwner($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->isOwner($user, $project);
    }

    public function submit(User $user, Project $project): bool
    {
        return $this->isOwner($user, $project);
    }

    private function isOwner(User $user, Project $project): bool
    {
        return $user->id === $project->user_id || $user->hasRole(Role::Admin);
    }
}
