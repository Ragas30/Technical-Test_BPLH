<?php

namespace App\DTO\User;

final readonly class AssignPermissionsDTO
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public array $permissions,
    ) {}
}
