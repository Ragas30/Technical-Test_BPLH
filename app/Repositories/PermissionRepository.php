<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

class PermissionRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new Permission;
    }

    public function allOrdered(): Collection
    {
        return Permission::orderBy('name')->get();
    }
}
