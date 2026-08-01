<?php

namespace App\DTO\User;

final readonly class AssignRolesDTO
{
    /**
     * @param  array<int, string>  $roles
     */
    public function __construct(
        public array $roles,
    ) {}
}
