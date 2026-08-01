<?php

namespace App\DTO\Review;

final readonly class ReviewDecisionDTO
{
    public function __construct(
        public ?string $notes = null,
    ) {}
}
