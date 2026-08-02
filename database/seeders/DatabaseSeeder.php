<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\ReviewStatus;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminSeeder::class,
            ReviewerSeeder::class,
            ApplicantSeeder::class,
            BulkDataSeeder::class,
        ]);

        $this->seedDemoData();
    }

    private function seedDemoData(): void
    {
        $applicant = User::where('email', 'applicant@docflow.test')->first();
        $reviewer = User::where('email', 'reviewer@docflow.test')->first();

        $projects = collect([
            'approved' => Project::factory()->count(4)->approved()->for($applicant)->create(),
            'rejected' => Project::factory()->count(2)->rejected()->for($applicant)->create(),
            'submitted' => Project::factory()->count(3)->submitted()->for($applicant)->create(),
            'draft' => Project::factory()->count(3)->for($applicant)->create(),
        ]);

        $projects['approved']->each(function (Project $project) use ($reviewer) {
            Review::factory()->approved()->create([
                'project_id' => $project->id,
                'reviewer_id' => $reviewer->id,
            ]);
        });

        $projects['rejected']->each(function (Project $project) use ($reviewer) {
            Review::factory()->rejected()->create([
                'project_id' => $project->id,
                'reviewer_id' => $reviewer->id,
            ]);
        });

        $projects['submitted']->each(function (Project $project) use ($reviewer) {
            Review::factory()->create([
                'project_id' => $project->id,
                'reviewer_id' => $reviewer->id,
                'status' => ReviewStatus::Pending,
            ]);
        });

        $projects['draft']->each(function (Project $project) {
            $project->update(['status' => ProjectStatus::Draft]);
        });
    }
}
