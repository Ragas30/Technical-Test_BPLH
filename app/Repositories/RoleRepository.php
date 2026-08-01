<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class RoleRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new Role;
    }

    public function allWithPermissions(): Collection
    {
        return Role::with('permissions')->orderBy('name')->get();
    }
}
