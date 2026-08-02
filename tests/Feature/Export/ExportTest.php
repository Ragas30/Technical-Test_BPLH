<?php

namespace Tests\Feature\Export;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExportTest extends TestCase
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

    public function test_export_requires_authentication(): void
    {
        $this->getJson('/api/export/projects')->assertStatus(401);
        $this->getJson('/api/export/projects/pdf')->assertStatus(401);
    }

    public function test_admin_can_export_projects_to_excel(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('admin'));

        Project::factory()->count(3)->create(['status' => ProjectStatus::Submitted]);

        $this->get('/api/export/projects')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Content-Disposition')
            ->assertHeaderContains('Content-Disposition', 'attachment');
    }

    public function test_admin_can_export_projects_to_pdf(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('admin'));

        Project::factory()->count(2)->create();

        $this->get('/api/export/projects/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition')
            ->assertHeaderContains('Content-Disposition', 'attachment');
    }

    public function test_admin_can_export_reviews_to_excel(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('admin'));

        Review::factory()->count(2)->create();

        $this->get('/api/export/reviews')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Content-Disposition')
            ->assertHeaderContains('Content-Disposition', 'attachment');
    }

    public function test_admin_can_export_reviews_to_pdf(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('admin'));

        Review::factory()->create();

        $this->get('/api/export/reviews/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition')
            ->assertHeaderContains('Content-Disposition', 'attachment');
    }

    public function test_reviewer_can_export_projects(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('reviewer'));

        Project::factory()->create();

        $this->get('/api/export/projects')->assertOk();
        $this->get('/api/export/projects/pdf')->assertOk();
    }

    public function test_applicant_cannot_export(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('applicant'));

        $this->getJson('/api/export/projects')->assertStatus(403);
        $this->getJson('/api/export/projects/pdf')->assertStatus(403);
        $this->getJson('/api/export/reviews')->assertStatus(403);
        $this->getJson('/api/export/reviews/pdf')->assertStatus(403);
    }
}
