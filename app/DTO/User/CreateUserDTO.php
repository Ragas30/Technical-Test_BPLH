<?php

namespace App\DTO\User;

final readonly class CreateUserDTO
{
    /**
     * @param  array<int, string>  $roles
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public bool $isActive,
        public array $roles,
        public array $permissions = [],
    ) {}
}
