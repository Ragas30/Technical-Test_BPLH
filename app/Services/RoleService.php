<?php

namespace App\Services;

use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use Illuminate\Database\Eloquent\Collection;

class RoleService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
    ) {}

    public function all(): Collection
    {
        return $this->roleRepository->allWithPermissions();
    }

    public function allPermissions(): Collection
    {
        return $this->permissionRepository->allOrdered();
    }
}
