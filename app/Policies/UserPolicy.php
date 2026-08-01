<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo(Permission::UserViewAny);
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo(Permission::UserCreate);
    }

    public function view(User $user, User $model): bool
    {
        return $this->ownsProfile($user, $model) || $user->checkPermissionTo(Permission::UserView);
    }

    public function update(User $user, User $model): bool
    {
        return $this->ownsProfile($user, $model) || $user->checkPermissionTo(Permission::UserUpdate);
    }

    public function changePassword(User $user, User $model): bool
    {
        return $this->ownsProfile($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->id !== $model->id && $user->checkPermissionTo(Permission::UserDelete);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->checkPermissionTo(Permission::UserRestore);
    }

    public function assignRoles(User $user, User $model): bool
    {
        return $user->checkPermissionTo(Permission::UserUpdate);
    }

    public function assignPermissions(User $user, User $model): bool
    {
        return $user->checkPermissionTo(Permission::PermissionAssign);
    }

    private function ownsProfile(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasRole(Role::Admin);
    }
}
