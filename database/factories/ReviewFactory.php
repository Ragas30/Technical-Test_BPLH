<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'reviewer_id' => User::factory(),
            'status' => ReviewStatus::Pending,
            'notes' => null,
            'reviewed_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewStatus::Approved,
            'notes' => fake()->sentence(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewStatus::Rejected,
            'notes' => fake()->sentence(),
            'reviewed_at' => now(),
        ]);
    }

    public function revision(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewStatus::Revision,
            'notes' => fake()->sentence(),
            'reviewed_at' => now(),
        ]);
    }
}
