<?php

namespace App\DTO\Review;

final readonly class ReviewQueryDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $status = null,
        public string $sortBy = 'created_at',
        public string $sortDir = 'desc',
        public int $perPage = 15,
    ) {}
}
