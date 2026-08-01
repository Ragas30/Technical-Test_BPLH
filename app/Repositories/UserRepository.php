<?php

namespace App\Repositories;

use App\DTO\User\UserQueryDTO;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        $this->model = new User;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function count(): int
    {
        return $this->model->count();
    }

    public function findWithTrashed(string $id): User
    {
        return User::withTrashed()->findOrFail($id);
    }

    public function paginateWithFilters(UserQueryDTO $dto): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles', 'permissions'])
            ->when($dto->withTrashed, fn ($query) => $query->withTrashed())
            ->when($dto->search !== null, fn ($query) => $query->where(function ($query) use ($dto): void {
                $pattern = '%'.addcslashes(mb_strtolower($dto->search), '%_\\').'%';

                $query->whereRaw('LOWER(name) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$pattern]);
            }))
            ->when($dto->role !== null, fn ($query) => $query->whereHas('roles', fn ($query) => $query->where('name', $dto->role)))
            ->when($dto->isActive !== null, fn ($query) => $query->where('is_active', $dto->isActive))
            ->orderBy($dto->sortBy, $dto->sortDir)
            ->paginate($dto->perPage);
    }
}
