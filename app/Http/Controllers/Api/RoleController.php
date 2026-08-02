<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roleService) {}

    #[Response(200, 'Daftar seluruh role beserta permission yang dimiliki.', examples: [[
        'data' => [
            [
                'id' => 1,
                'name' => 'admin',
                'guard_name' => 'web',
                'permissions' => ['dashboard.view', 'user.view_any'],
                'created_at' => '2026-07-01T09:00:00+07:00',
                'updated_at' => '2026-07-01T09:00:00+07:00',
            ],
        ],
    ]])]
    public function index(): AnonymousResourceCollection
    {
        return RoleResource::collection($this->roleService->all());
    }

    #[Response(200, 'Daftar seluruh permission yang tersedia.', examples: [[
        'data' => [
            ['id' => 1, 'name' => 'dashboard.view', 'guard_name' => 'web'],
        ],
    ]])]
    public function permissions(): AnonymousResourceCollection
    {
        return PermissionResource::collection($this->roleService->allPermissions());
    }
}
