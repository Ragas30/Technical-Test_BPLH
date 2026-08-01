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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        return UserResource::collection($this->userService->paginate($request->toDTO()));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        return UserResource::make($this->userService->create($request->toDTO()))->response()->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return UserResource::make($user->load('roles', 'permissions'));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $this->authorize('update', $user);

        return UserResource::make($this->userService->update($user->id, $request->toDTO()));
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user->id);

        return response()->json(['message' => 'Pengguna berhasil dihapus.']);
    }

    public function restore(string $user): UserResource
    {
        $model = $this->userService->findWithTrashed($user);

        $this->authorize('restore', $model);

        return UserResource::make($this->userService->restore($model->id));
    }

    public function assignRoles(AssignRolesRequest $request, User $user): UserResource
    {
        $this->authorize('assignRoles', $user);

        return UserResource::make($this->userService->assignRoles($user->id, $request->toDTO()));
    }

    public function assignPermissions(AssignPermissionsRequest $request, User $user): UserResource
    {
        $this->authorize('assignPermissions', $user);

        return UserResource::make($this->userService->assignPermissions($user->id, $request->toDTO()));
    }
}
