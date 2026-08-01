<?php

namespace Tests\Feature\Document;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentTest extends TestCase
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

    public function test_documents_require_authentication(): void
    {
        $project = Project::factory()->create();

        $this->getJson('/api/projects/'.$project->id.'/documents')->assertStatus(401);

        $this->getJson('/api/documents/'.$project->id.'/download')->assertStatus(401);
    }

    public function test_owner_can_upload_multiple_documents(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $applicant->id]);

        Sanctum::actingAs($applicant);

        $this->post('/api/projects/'.$project->id.'/documents', [
            'documents' => [
                UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('data.xlsx', 200, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ],
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseCount('project_documents', 2);

        $paths = ProjectDocument::query()->pluck('path');
        $paths->each(fn (string $path) => Storage::disk('local')->assertExists($path));

        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'action' => 'document_uploaded',
        ]);
    }

    public function test_upload_rejects_disallowed_extension(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $applicant->id]);

        Sanctum::actingAs($applicant);

        $this->postJson('/api/projects/'.$project->id.'/documents', [
            'documents' => [UploadedFile::fake()->create('script.exe', 100)],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('documents.0');

        Storage::disk('local')->assertDirectoryEmpty('project-documents');
    }

    public function test_upload_requires_at_least_one_document(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $applicant->id]);

        Sanctum::actingAs($applicant);

        $this->postJson('/api/projects/'.$project->id.'/documents', [
            'documents' => [],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('documents');

        $this->assertDatabaseCount('project_documents', 0);
    }

    public function test_applicant_cannot_upload_to_other_project(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create();

        Sanctum::actingAs($applicant);

        $this->postJson('/api/projects/'.$project->id.'/documents', [
            'documents' => [UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')],
        ])->assertStatus(403);

        $this->assertDatabaseCount('project_documents', 0);
    }

    public function test_reviewer_cannot_upload_documents(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $reviewer = $this->userWithRole('reviewer');
        $project = Project::factory()->create();

        Sanctum::actingAs($reviewer);

        $this->postJson('/api/projects/'.$project->id.'/documents', [
            'documents' => [UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')],
        ])->assertStatus(403);
    }

    public function test_owner_can_list_documents(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $applicant->id]);

        Sanctum::actingAs($applicant);

        $this->post('/api/projects/'.$project->id.'/documents', [
            'documents' => [UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')],
        ], ['Accept' => 'application/json']);

        $this->getJson('/api/projects/'.$project->id.'/documents')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.original_name', 'laporan.pdf')
            ->assertJsonStructure(['data' => [['id', 'name', 'mime_type', 'extension', 'size', 'uploader', 'created_at']]]);
    }

    public function test_reviewer_can_list_and_download_documents(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $applicant->id]);

        Sanctum::actingAs($applicant);

        $this->post('/api/projects/'.$project->id.'/documents', [
            'documents' => [UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')],
        ], ['Accept' => 'application/json']);

        $document = ProjectDocument::query()->firstOrFail();

        $reviewer = $this->userWithRole('reviewer');
        Sanctum::actingAs($reviewer);

        $this->getJson('/api/projects/'.$project->id.'/documents')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->get('/api/documents/'.$document->id.'/download')
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=laporan.pdf');
    }

    public function test_owner_can_download_document(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $applicant->id]);

        Sanctum::actingAs($applicant);

        $this->post('/api/projects/'.$project->id.'/documents', [
            'documents' => [UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')],
        ], ['Accept' => 'application/json']);

        $document = ProjectDocument::query()->firstOrFail();

        $this->get('/api/documents/'.$document->id.'/download')
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=laporan.pdf');
    }

    public function test_owner_can_preview_pdf_document(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $applicant->id]);

        Sanctum::actingAs($applicant);

        $this->post('/api/projects/'.$project->id.'/documents', [
            'documents' => [UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')],
        ], ['Accept' => 'application/json']);

        $document = ProjectDocument::query()->firstOrFail();

        $this->get('/api/documents/'.$document->id.'/preview')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_owner_can_replace_document(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $applicant->id]);

        Sanctum::actingAs($applicant);

        $this->post('/api/projects/'.$project->id.'/documents', [
            'documents' => [UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')],
        ], ['Accept' => 'application/json']);

        $document = ProjectDocument::query()->firstOrFail();
        $oldPath = $document->path;

        $this->postJson('/api/documents/'.$document->id.'/replace', [
            'file' => UploadedFile::fake()->create('laporan-revisi.pdf', 150, 'application/pdf'),
        ])->assertOk();

        $this->assertDatabaseHas('project_documents', [
            'id' => $document->id,
            'original_name' => 'laporan-revisi.pdf',
            'size' => 150 * 1024,
        ]);

        Storage::disk('local')->assertMissing($oldPath);

        $document->refresh();
        Storage::disk('local')->assertExists($document->path);
    }

    public function test_owner_can_delete_document(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $applicant = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $applicant->id]);

        Sanctum::actingAs($applicant);

        $this->post('/api/projects/'.$project->id.'/documents', [
            'documents' => [UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')],
        ], ['Accept' => 'application/json']);

        $document = ProjectDocument::query()->firstOrFail();

        $this->deleteJson('/api/documents/'.$document->id)
            ->assertOk()
            ->assertJsonPath('message', 'Dokumen berhasil dihapus.');

        $this->assertSoftDeleted('project_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->path);

        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'action' => 'document_deleted',
        ]);
    }

    public function test_applicant_cannot_delete_other_document(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $owner = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($owner);

        $this->post('/api/projects/'.$project->id.'/documents', [
            'documents' => [UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')],
        ], ['Accept' => 'application/json']);

        $document = ProjectDocument::query()->firstOrFail();

        $other = $this->userWithRole('applicant');
        Sanctum::actingAs($other);

        $this->deleteJson('/api/documents/'.$document->id)->assertStatus(403);
        $this->assertNotSoftDeleted('project_documents', ['id' => $document->id]);
    }

    public function test_applicant_cannot_download_other_document(): void
    {
        $this->seedRoles();
        Storage::fake('local');

        $owner = $this->userWithRole('applicant');
        $project = Project::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($owner);

        $this->post('/api/projects/'.$project->id.'/documents', [
            'documents' => [UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')],
        ], ['Accept' => 'application/json']);

        $document = ProjectDocument::query()->firstOrFail();

        $other = $this->userWithRole('applicant');
        Sanctum::actingAs($other);

        $this->get('/api/documents/'.$document->id.'/download')->assertStatus(403);
    }
}
