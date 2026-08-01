<?php

namespace App\Services;

use App\DTO\User\AssignPermissionsDTO;
use App\DTO\User\AssignRolesDTO;
use App\DTO\User\CreateUserDTO;
use App\DTO\User\UpdateUserDTO;
use App\DTO\User\UserQueryDTO;
use App\Enums\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(private readonly UserRepository $userRepository) {}

    public function paginate(UserQueryDTO $dto): LengthAwarePaginator
    {
        return $this->userRepository->paginateWithFilters($dto);
    }

    public function findWithTrashed(string $id): User
    {
        return $this->userRepository->findWithTrashed($id);
    }

    public function create(CreateUserDTO $dto): User
    {
        return DB::transaction(function () use ($dto): User {
            $user = $this->userRepository->create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => $dto->password,
                'is_active' => $dto->isActive,
            ]);

            $user->syncRoles($dto->roles);

            if ($dto->permissions !== []) {
                $user->syncPermissions($dto->permissions);
            }

            return $user->load('roles', 'permissions');
        });
    }

    public function update(string $id, UpdateUserDTO $dto): User
    {
        return DB::transaction(function () use ($id, $dto): User {
            $user = $this->userRepository->findOrFail($id);

            if ($user->id === auth()->id() && ! $dto->isActive) {
                throw ValidationException::withMessages([
                    'is_active' => ['Tidak dapat menonaktifkan akun sendiri.'],
                ]);
            }

            $attributes = [
                'name' => $dto->name,
                'email' => $dto->email,
                'is_active' => $dto->isActive,
            ];

            if ($dto->password !== null) {
                $attributes['password'] = $dto->password;
            }

            $this->userRepository->update($id, $attributes);
            $user->refresh();

            if ($dto->roles !== null) {
                $user->syncRoles($dto->roles);
            }

            if ($dto->permissions !== null) {
                $user->syncPermissions($dto->permissions);
            }

            return $user->load('roles', 'permissions');
        });
    }

    public function delete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $user = $this->userRepository->findOrFail($id);

            if ($user->id === auth()->id()) {
                throw ValidationException::withMessages([
                    'id' => ['Tidak dapat menghapus akun sendiri.'],
                ]);
            }

            $user->tokens()->delete();
            $user->delete();
        });
    }

    public function restore(string $id): User
    {
        $user = $this->userRepository->findWithTrashed($id);
        $user->restore();

        return $user->load('roles', 'permissions');
    }

    public function assignRoles(string $id, AssignRolesDTO $dto): User
    {
        $user = $this->userRepository->findOrFail($id);

        if ($user->id === auth()->id() && $user->hasRole(Role::Admin) && ! in_array(Role::Admin->value, $dto->roles, true)) {
            throw ValidationException::withMessages([
                'roles' => ['Tidak dapat menghapus role admin dari akun sendiri.'],
            ]);
        }

        $user->syncRoles($dto->roles);

        return $user->load('roles', 'permissions');
    }

    public function assignPermissions(string $id, AssignPermissionsDTO $dto): User
    {
        $user = $this->userRepository->findOrFail($id);
        $user->syncPermissions($dto->permissions);

        return $user->load('roles', 'permissions');
    }
}
