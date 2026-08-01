<?php

namespace Tests\Feature\Project;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectTest extends TestCase
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

    public function test_projects_require_authentication(): void
    {
        $this->getJson('/api/projects')->assertStatus(401);
    }

    public function test_admin_can_list_all_projects(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('admin'));

        Project::factory()->count(3)->create();

        $this->getJson('/api/projects')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(3, 'data');
    }

    public function test_reviewer_can_list_all_projects(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('reviewer'));

        Project::factory()->count(2)->create();

        $this->getJson('/api/projects')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_applicant_cannot_list_all_projects(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('applicant'));

        $this->getJson('/api/projects')->assertStatus(403);
    }

    public function test_applicant_can_list_only_own_projects(): void
    {
        $this->seedRoles();
        $applicant = $this->userWithRole('applicant');
        $other = User::factory()->create();

        Project::factory()->count(2)->create(['user_id' => $applicant->id]);
        Project::factory()->count(3)->create(['user_id' => $other->id]);

        Sanctum::actingAs($applicant);

        $this->getJson('/api/projects/mine')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.user.id', $applicant->id);
    }

    public function test_applicant_can_search_and_filter_own_projects(): void
    {
        $this->seedRoles();
        $applicant = $this->userWithRole('applicant');

        Project::factory()->create([
            'user_id' => $applicant->id,
            'title' => 'Pengajuan Izin Lingkungan',
            'status' => ProjectStatus::Draft,
        ]);
        Project::factory()->create([
            'user_id' => $applicant->id,
            'title' => 'Pengajuan AMDAL',
            'status' => ProjectStatus::Submitted,
        ]);

        Sanctum::actingAs($applicant);

        $this->getJson('/api/projects/mine?search=izin')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Pengajuan Izin Lingkungan');

        $this->getJson('/api/projects/mine?status=submitted')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'submitted');
    }

    public function test_applicant_can_create_project(): void
    {
        $this->seedRoles();
        $applicant = $this->userWithRole('applicant');

        Sanctum::actingAs($applicant);

        $response = $this->postJson('/api/projects', [
            'title' => 'Pengajuan Izin Lingkungan',
            'description' => 'Deskripsi project.',
        ])->assertStatus(201);

        $response->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.title', 'Pengajuan Izin Lingkungan')
            ->assertJsonPath('data.user.id', $applicant->id)
            ->assertJsonStructure([
                'data' => ['id', 'project_number', 'slug', 'title', 'description', 'status', 'created_at'],
            ]);

        $this->assertStringStartsWith('PRJ-'.now()->year.'-', $response->json('data.project_number'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'project_created']);
    }

    public function test_create_project_requires_title(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('applicant'));

        $this->postJson('/api/projects', ['description' => 'Tanpa judul'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_project_number_is_incremental_within_year(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('applicant'));

        $first = $this->postJson('/api/projects', ['title' => 'Project Satu'])->json('data.project_number');
        $second = $this->postJson('/api/projects', ['title' => 'Project Dua'])->json('data.project_number');

        $this->assertNotEquals($first, $second);
        $this->assertLessThan($second, $first);
    }

    public function test_owner_can_update_draft_project(): void
    {
        $this->seedRoles();
        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $applicant->id]);

        Sanctum::actingAs($applicant);

        $this->putJson("/api/projects/{$project->id}", [
            'title' => 'Judul Diperbarui',
            'description' => 'Deskripsi baru.',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Judul Diperbarui');

        $this->assertDatabaseHas('activity_logs', ['action' => 'project_updated']);
    }

    public function test_applicant_cannot_update_other_users_project(): void
    {
        $this->seedRoles();
        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => User::factory()->create()->id]);

        Sanctum::actingAs($applicant);

        $this->putJson("/api/projects/{$project->id}", [
            'title' => 'Hack',
        ])->assertStatus(403);
    }

    public function test_applicant_cannot_update_submitted_project(): void
    {
        $this->seedRoles();
        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create([
            'user_id' => $applicant->id,
            'status' => ProjectStatus::Submitted,
        ]);

        Sanctum::actingAs($applicant);

        $this->putJson("/api/projects/{$project->id}", [
            'title' => 'Ubah',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_owner_can_submit_draft_project(): void
    {
        $this->seedRoles();
        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $applicant->id]);

        Sanctum::actingAs($applicant);

        $this->postJson("/api/projects/{$project->id}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', 'submitted');

        $this->assertNotNull($project->fresh()->submitted_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'project_submitted']);
    }

    public function test_applicant_cannot_submit_already_submitted_project(): void
    {
        $this->seedRoles();
        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create([
            'user_id' => $applicant->id,
            'status' => ProjectStatus::Submitted,
        ]);

        Sanctum::actingAs($applicant);

        $this->postJson("/api/projects/{$project->id}/submit")
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_owner_can_delete_draft_project(): void
    {
        $this->seedRoles();
        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $applicant->id]);

        Sanctum::actingAs($applicant);

        $this->deleteJson("/api/projects/{$project->id}")->assertOk();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'project_deleted']);
    }

    public function test_applicant_cannot_delete_submitted_project(): void
    {
        $this->seedRoles();
        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create([
            'user_id' => $applicant->id,
            'status' => ProjectStatus::Submitted,
        ]);

        Sanctum::actingAs($applicant);

        $this->deleteJson("/api/projects/{$project->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_reviewer_cannot_create_project(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('reviewer'));

        $this->postJson('/api/projects', ['title' => 'Tidak boleh'])->assertStatus(403);
    }

    public function test_reviewer_can_view_project(): void
    {
        $this->seedRoles();
        $reviewer = $this->userWithRole('reviewer');
        $project = Project::factory()->create();

        Sanctum::actingAs($reviewer);

        $this->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $project->id);
    }
}
