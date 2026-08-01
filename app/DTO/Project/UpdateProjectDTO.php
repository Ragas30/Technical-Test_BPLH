<?php

namespace App\DTO\Project;

final readonly class UpdateProjectDTO
{
    public function __construct(
        public string $title,
        public ?string $description,
    ) {}
}
