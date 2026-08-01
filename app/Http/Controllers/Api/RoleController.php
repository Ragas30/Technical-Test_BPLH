<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roleService) {}

    public function index(): AnonymousResourceCollection
    {
        return RoleResource::collection($this->roleService->all());
    }

    public function permissions(): AnonymousResourceCollection
    {
        return PermissionResource::collection($this->roleService->allPermissions());
    }
}
