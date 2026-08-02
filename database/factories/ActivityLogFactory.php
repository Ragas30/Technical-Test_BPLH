<?php

namespace Database\Factories;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = fake()->randomElement(ActivityAction::values());

        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'action' => $action,
            'description' => fake()->sentence(),
            'properties' => ['project_number' => 'PRJ-'.now()->format('Y').'-00001'],
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
