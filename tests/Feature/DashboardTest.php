<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\ProjectStatus;
use App\Enums\ReviewStatus;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
    }

    public function test_admin_receives_admin_dashboard(): void
    {
        $this->seedRoles();

        $admin = $this->userWithRole('admin');
        $other = User::factory()->create();

        Project::factory()->count(3)->create(['user_id' => $other->id, 'status' => ProjectStatus::Submitted]);
        Project::factory()->create(['user_id' => $other->id, 'status' => ProjectStatus::Approved]);
        ActivityLog::create([
            'user_id' => $other->id,
            'action' => ActivityAction::ProjectSubmitted,
            'description' => 'Project submitted',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/dashboard')->assertOk();

        $response->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.statistics.total_users', 2)
            ->assertJsonPath('data.statistics.total_projects', 4)
            ->assertJsonPath('data.statistics.submitted', 3)
            ->assertJsonPath('data.statistics.approved', 1)
            ->assertJsonCount(6, 'data.monthly_stats')
            ->assertJsonCount(1, 'data.recent_activities');
    }

    public function test_applicant_receives_dashboard_scoped_to_own_projects(): void
    {
        $this->seedRoles();

        $applicant = $this->userWithRole('applicant');
        $other = User::factory()->create();

        Project::factory()->count(2)->create(['user_id' => $applicant->id, 'status' => ProjectStatus::Draft]);
        Project::factory()->count(3)->create(['user_id' => $other->id, 'status' => ProjectStatus::Approved]);

        Sanctum::actingAs($applicant);

        $response = $this->getJson('/api/dashboard')->assertOk();

        $response->assertJsonPath('data.role', 'applicant')
            ->assertJsonPath('data.statistics.total_projects', 2)
            ->assertJsonPath('data.statistics.draft', 2)
            ->assertJsonCount(6, 'data.monthly_stats');
    }

    public function test_reviewer_receives_review_workload_dashboard(): void
    {
        $this->seedRoles();

        $reviewer = $this->userWithRole('reviewer');
        $applicant = User::factory()->create();

        $project = Project::factory()->create(['user_id' => $applicant->id, 'status' => ProjectStatus::UnderReview]);
        Review::factory()->create(['project_id' => $project->id, 'reviewer_id' => $reviewer->id, 'status' => ReviewStatus::Pending]);
        Review::factory()->approved()->create(['project_id' => $project->id, 'reviewer_id' => $reviewer->id, 'status' => ReviewStatus::Approved]);
        Review::factory()->rejected()->create(['project_id' => $project->id, 'reviewer_id' => $reviewer->id, 'status' => ReviewStatus::Rejected]);

        Sanctum::actingAs($reviewer);

        $response = $this->getJson('/api/dashboard')->assertOk();

        $response->assertJsonPath('data.role', 'reviewer')
            ->assertJsonPath('data.statistics.total_reviews', 3)
            ->assertJsonPath('data.statistics.pending', 1)
            ->assertJsonPath('data.statistics.approved', 1)
            ->assertJsonPath('data.statistics.rejected', 1)
            ->assertJsonCount(6, 'data.monthly_stats')
            ->assertJsonCount(3, 'data.recent_reviews');
    }
}
