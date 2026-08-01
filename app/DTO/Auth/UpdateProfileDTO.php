<?php

namespace App\DTO\Auth;

final readonly class UpdateProfileDTO
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}
