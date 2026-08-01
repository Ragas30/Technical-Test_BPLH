<?php

namespace App\DTO\User;

final readonly class UpdateUserDTO
{
    /**
     * @param  array<int, string>|null  $roles
     * @param  array<int, string>|null  $permissions
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        public bool $isActive = true,
        public ?array $roles = null,
        public ?array $permissions = null,
    ) {}
}
