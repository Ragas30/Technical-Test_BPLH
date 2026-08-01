<?php

namespace App\DTO\User;

final readonly class UserQueryDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $role = null,
        public ?bool $isActive = null,
        public string $sortBy = 'created_at',
        public string $sortDir = 'desc',
        public int $perPage = 15,
        public bool $withTrashed = false,
    ) {}
}
