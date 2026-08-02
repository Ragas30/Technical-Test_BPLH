<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'user_id' => User::factory(),
            'project_number' => 'PRJ-'.now()->format('Y').'-'.str_pad((string) self::nextProjectNumber(), 5, '0', STR_PAD_LEFT),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999),
            'title' => $title,
            'description' => fake()->paragraph(),
            'status' => ProjectStatus::Draft,
            'submitted_at' => null,
        ];
    }

    private static function nextProjectNumber(): int
    {
        static $start;

        if ($start === null) {
            $start = (int) Project::withTrashed()->max('project_number');
        }

        return ++$start;
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Approved,
            'submitted_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Rejected,
            'submitted_at' => now()->subDays(rand(1, 30)),
        ]);
    }
}
