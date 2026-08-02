<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AssignPermissionsRequest;
use App\Http\Requests\User\AssignRolesRequest;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    private const USER_EXAMPLE = [
        'id' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        'name' => 'Budi Santoso',
        'email' => 'budi@docflow.test',
        'is_active' => true,
        'roles' => ['applicant'],
        'permissions' => ['dashboard.view', 'project.view', 'project.create'],
        'email_verified_at' => '2026-08-01T09:00:00+07:00',
        'created_at' => '2026-07-01T09:00:00+07:00',
        'updated_at' => '2026-08-01T09:00:00+07:00',
        'deleted_at' => null,
    ];

    private const USER_PAGE_EXAMPLE = [
        'data' => [self::USER_EXAMPLE],
        'meta' => [
            'current_page' => 1,
            'from' => 1,
            'last_page' => 1,
            'path' => 'http://localhost:8000/api/users',
            'per_page' => 15,
            'to' => 3,
            'total' => 3,
        ],
    ];

    public function __construct(private readonly UserService $userService) {}

    #[Response(200, 'Daftar pengguna dengan pagination.', examples: [self::USER_PAGE_EXAMPLE])]
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        return UserResource::collection($this->userService->paginate($request->toDTO()));
    }

    #[Response(201, 'Pengguna berhasil dibuat.', examples: [['data' => self::USER_EXAMPLE]])]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        return UserResource::make($this->userService->create($request->toDTO()))->response()->setStatusCode(201);
    }

    #[Response(200, 'Detail pengguna.', examples: [['data' => self::USER_EXAMPLE]])]
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return UserResource::make($user->load('roles', 'permissions'));
    }

    #[Response(200, 'Pengguna berhasil diperbarui.', examples: [['data' => self::USER_EXAMPLE]])]
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $this->authorize('update', $user);

        return UserResource::make($this->userService->update($user->id, $request->toDTO()));
    }

    #[Response(200, 'Pengguna berhasil dihapus (soft delete).', examples: [['message' => 'Pengguna berhasil dihapus.']])]
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user->id);

        return response()->json(['message' => 'Pengguna berhasil dihapus.']);
    }

    #[Response(200, 'Pengguna berhasil dipulihkan.', examples: [['data' => self::USER_EXAMPLE]])]
    public function restore(string $user): UserResource
    {
        $model = $this->userService->findWithTrashed($user);

        $this->authorize('restore', $model);

        return UserResource::make($this->userService->restore($model->id));
    }

    #[Response(200, 'Role pengguna berhasil diperbarui.', examples: [['data' => self::USER_EXAMPLE]])]
    public function assignRoles(AssignRolesRequest $request, User $user): UserResource
    {
        $this->authorize('assignRoles', $user);

        return UserResource::make($this->userService->assignRoles($user->id, $request->toDTO()));
    }

    #[Response(200, 'Permission pengguna berhasil diperbarui.', examples: [['data' => self::USER_EXAMPLE]])]
    public function assignPermissions(AssignPermissionsRequest $request, User $user): UserResource
    {
        $this->authorize('assignPermissions', $user);

        return UserResource::make($this->userService->assignPermissions($user->id, $request->toDTO()));
    }
}
