<?php

namespace App\DTO\ActivityLog;

final readonly class ActivityLogQueryDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $action = null,
        public int $perPage = 15,
    ) {}
}
