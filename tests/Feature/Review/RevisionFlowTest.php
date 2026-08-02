<?php

namespace Tests\Feature\Review;

use App\Enums\ProjectStatus;
use App\Enums\ReviewStatus;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RevisionFlowTest extends TestCase
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

    /**
     * Buat project draft milik pemohon lalu ajukan hingga status "sedang direvisi".
     */
    private function projectUnderRevision(User $applicant, User $reviewer): Project
    {
        $project = Project::factory()->create(['user_id' => $applicant->id]);

        Sanctum::actingAs($applicant);
        $this->postJson('/api/projects/'.$project->id.'/submit')->assertOk();

        Sanctum::actingAs($reviewer);
        $this->postJson('/api/projects/'.$project->id.'/reviews')->assertCreated();
        $reviewId = $project->fresh()->reviews()->first()->id;

        $this->postJson('/api/reviews/'.$reviewId.'/revision', [
            'notes' => 'Mohon lengkapi dokumen pendukung.',
        ])->assertOk();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Revision->value,
        ]);

        return $project;
    }

    public function test_applicant_can_upload_new_document_during_revision(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $applicant = $this->userWithRole('applicant');
        $reviewer = $this->userWithRole('reviewer');

        $project = $this->projectUnderRevision($applicant, $reviewer);

        Sanctum::actingAs($applicant);

        $this->post('/api/projects/'.$project->id.'/documents', [
            'documents' => [
                UploadedFile::fake()->create('revisi-laporan.pdf', 100, 'application/pdf'),
            ],
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonCount(1, 'data');

        $this->assertDatabaseCount('project_documents', 1);
    }

    public function test_applicant_can_resubmit_project_after_revision(): void
    {
        $this->seedRoles();

        $applicant = $this->userWithRole('applicant');
        $reviewer = $this->userWithRole('reviewer');

        $project = $this->projectUnderRevision($applicant, $reviewer);

        Sanctum::actingAs($applicant);

        $this->postJson('/api/projects/'.$project->id.'/submit')
            ->assertOk()
            ->assertJsonPath('data.status', ProjectStatus::Submitted->value);

        $this->assertNotNull($project->fresh()->submitted_at);
    }

    public function test_applicant_cannot_edit_title_during_revision(): void
    {
        $this->seedRoles();

        $applicant = $this->userWithRole('applicant');
        $reviewer = $this->userWithRole('reviewer');

        $project = $this->projectUnderRevision($applicant, $reviewer);

        Sanctum::actingAs($applicant);

        $this->putJson('/api/projects/'.$project->id, [
            'title' => 'Judul Tidak Boleh Diubah',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_full_revision_workflow_ends_in_approval(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $applicant = $this->userWithRole('applicant');
        $reviewer = $this->userWithRole('reviewer');

        $project = $this->projectUnderRevision($applicant, $reviewer);

        Sanctum::actingAs($applicant);

        $this->post('/api/projects/'.$project->id.'/documents', [
            'documents' => [
                UploadedFile::fake()->create('laporan-final.pdf', 100, 'application/pdf'),
            ],
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->postJson('/api/projects/'.$project->id.'/submit')
            ->assertOk()
            ->assertJsonPath('data.status', ProjectStatus::Submitted->value);

        Sanctum::actingAs($reviewer);

        $this->postJson('/api/projects/'.$project->id.'/reviews')
            ->assertCreated()
            ->assertJsonPath('data.status', ReviewStatus::UnderReview->value);

        $this->assertDatabaseCount('reviews', 2);

        $newReviewId = $project->fresh()->reviews()
            ->where('status', ReviewStatus::UnderReview->value)
            ->firstOrFail()
            ->id;

        $this->postJson('/api/reviews/'.$newReviewId.'/approve', [
            'notes' => 'Revisi telah dilengkapi dan sesuai.',
        ])->assertOk()
            ->assertJsonPath('data.status', ReviewStatus::Approved->value);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Approved->value,
        ]);
    }
}
