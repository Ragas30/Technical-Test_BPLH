<?php

namespace App\DTO\Project;

final readonly class CreateProjectDTO
{
    public function __construct(
        public string $title,
        public ?string $description,
    ) {}
}
