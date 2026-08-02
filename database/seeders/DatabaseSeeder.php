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
    private int $demoProjectSequence = 0;

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
        if (Project::where('slug', 'like', 'demo-project-%')->exists()) {
            $this->command?->info('Demo data sudah ada. Seeder dilewati.');

            return;
        }

        $applicant = User::where('email', 'applicant@docflow.test')->first();
        $reviewer = User::where('email', 'reviewer@docflow.test')->first();

        $projects = collect([
            'approved' => Project::factory()->count(4)->approved()->for($applicant)->sequence(fn () => $this->demoProjectIdentifiers())->create(),
            'rejected' => Project::factory()->count(2)->rejected()->for($applicant)->sequence(fn () => $this->demoProjectIdentifiers())->create(),
            'submitted' => Project::factory()->count(3)->submitted()->for($applicant)->sequence(fn () => $this->demoProjectIdentifiers())->create(),
            'draft' => Project::factory()->count(3)->for($applicant)->sequence(fn () => $this->demoProjectIdentifiers())->create(),
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

    /**
     * @return array{project_number: string, slug: string}
     */
    private function demoProjectIdentifiers(): array
    {
        $this->demoProjectSequence++;
        $number = $this->nextProjectNumber() + $this->demoProjectSequence - 1;

        return [
            'project_number' => 'PRJ-'.now()->format('Y').'-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT),
            'slug' => 'demo-project-'.$number,
        ];
    }

    private function nextProjectNumber(): int
    {
        static $startNumber;

        if ($startNumber === null) {
            $currentMax = (int) preg_replace('/\D+/', '', (string) Project::max('project_number'));
            $startNumber = max($currentMax + 1, 1);
        }

        return $startNumber;
    }
}
