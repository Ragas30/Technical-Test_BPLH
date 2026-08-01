<?php

namespace App\DTO\Auth;

final readonly class ResetPasswordDTO
{
    public function __construct(
        public string $token,
        public string $email,
        public string $password,
    ) {}
}
