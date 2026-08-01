<?php

namespace App\DTO\Auth;

final readonly class ChangePasswordDTO
{
    public function __construct(
        public string $newPassword,
    ) {}
}
