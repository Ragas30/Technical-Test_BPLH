<?php

namespace Tests\Feature\Review;

use App\Enums\ProjectStatus;
use App\Enums\ReviewAction;
use App\Enums\ReviewStatus;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewTest extends TestCase
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

    private function submittedProject(): Project
    {
        return Project::factory()->submitted()->create([
            'user_id' => User::factory()->create()->id,
        ]);
    }

    private function startReview(User $reviewer, Project $project): Review
    {
        $this->postJson('/api/projects/'.$project->id.'/reviews')->assertCreated();

        return Review::query()->where('project_id', $project->id)->firstOrFail();
    }

    public function test_review_endpoints_require_authentication(): void
    {
        $review = Review::factory()->create();

        $this->getJson('/api/reviews')->assertStatus(401);
        $this->getJson('/api/reviews/'.$review->id)->assertStatus(401);
    }

    public function test_reviewer_can_start_review_on_submitted_project(): void
    {
        $this->seedRoles();
        $reviewer = $this->userWithRole('reviewer');
        $project = $this->submittedProject();

        Sanctum::actingAs($reviewer);

        $this->postJson('/api/projects/'.$project->id.'/reviews')
            ->assertCreated()
            ->assertJsonPath('data.status', ReviewStatus::UnderReview->value)
            ->assertJsonStructure(['data' => ['id', 'project', 'reviewer', 'status', 'created_at']]);

        $this->assertDatabaseHas('reviews', [
            'project_id' => $project->id,
            'reviewer_id' => $reviewer->id,
            'status' => ReviewStatus::UnderReview->value,
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::UnderReview->value,
        ]);

        $this->assertDatabaseHas('review_logs', [
            'project_id' => $project->id,
            'action' => ReviewAction::UnderReview->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'action' => 'review_started',
        ]);
    }

    public function test_cannot_start_review_on_draft_project(): void
    {
        $this->seedRoles();
        $reviewer = $this->userWithRole('reviewer');
        $project = Project::factory()->create();

        Sanctum::actingAs($reviewer);

        $this->postJson('/api/projects/'.$project->id.'/reviews')->assertStatus(403);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_applicant_cannot_start_review(): void
    {
        $this->seedRoles();
        $applicant = $this->userWithRole('applicant');
        $project = $this->submittedProject();

        Sanctum::actingAs($applicant);

        $this->postJson('/api/projects/'.$project->id.'/reviews')->assertStatus(403);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_cannot_start_review_twice_on_same_project(): void
    {
        $this->seedRoles();
        $reviewer = $this->userWithRole('reviewer');
        $project = $this->submittedProject();

        Sanctum::actingAs($reviewer);

        $this->postJson('/api/projects/'.$project->id.'/reviews')->assertCreated();
        $this->postJson('/api/projects/'.$project->id.'/reviews')->assertStatus(403);
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_reviewer_can_approve_review(): void
    {
        $this->seedRoles();
        $reviewer = $this->userWithRole('reviewer');
        $project = $this->submittedProject();

        Sanctum::actingAs($reviewer);

        $review = $this->startReview($reviewer, $project);

        $this->postJson('/api/reviews/'.$review->id.'/approve', [
            'notes' => 'Dokumen lengkap dan sesuai.',
        ])->assertOk()
            ->assertJsonPath('data.status', ReviewStatus::Approved->value);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => ReviewStatus::Approved->value,
            'notes' => 'Dokumen lengkap dan sesuai.',
        ]);

        $this->assertNotNull($review->refresh()->reviewed_at);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Approved->value,
        ]);

        $this->assertDatabaseHas('review_logs', [
            'review_id' => $review->id,
            'action' => ReviewAction::Approved->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'action' => 'review_approved',
        ]);
    }

    public function test_reviewer_can_reject_review_with_notes(): void
    {
        $this->seedRoles();
        $reviewer = $this->userWithRole('reviewer');
        $project = $this->submittedProject();

        Sanctum::actingAs($reviewer);

        $review = $this->startReview($reviewer, $project);

        $this->postJson('/api/reviews/'.$review->id.'/reject', [
            'notes' => 'Dokumen tidak sesuai ketentuan.',
        ])->assertOk()
            ->assertJsonPath('data.status', ReviewStatus::Rejected->value);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => ReviewStatus::Rejected->value,
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Rejected->value,
        ]);

        $this->assertDatabaseHas('review_logs', [
            'review_id' => $review->id,
            'action' => ReviewAction::Rejected->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'action' => 'review_rejected',
        ]);
    }

    public function test_reject_requires_notes(): void
    {
        $this->seedRoles();
        $reviewer = $this->userWithRole('reviewer');
        $project = $this->submittedProject();

        Sanctum::actingAs($reviewer);

        $review = $this->startReview($reviewer, $project);

        $this->postJson('/api/reviews/'.$review->id.'/reject')
            ->assertStatus(422)
            ->assertJsonValidationErrors('notes');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::UnderReview->value,
        ]);
    }

    public function test_reviewer_can_request_revision(): void
    {
        $this->seedRoles();
        $reviewer = $this->userWithRole('reviewer');
        $project = $this->submittedProject();

        Sanctum::actingAs($reviewer);

        $review = $this->startReview($reviewer, $project);

        $this->postJson('/api/reviews/'.$review->id.'/revision', [
            'notes' => 'Mohon lengkapi dokumen pendukung.',
        ])->assertOk()
            ->assertJsonPath('data.status', ReviewStatus::Revision->value);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => ReviewStatus::Revision->value,
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => ProjectStatus::Revision->value,
        ]);

        $this->assertDatabaseHas('review_logs', [
            'review_id' => $review->id,
            'action' => ReviewAction::Revision->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'action' => 'revision_requested',
        ]);
    }

    public function test_cannot_decide_review_when_not_under_review(): void
    {
        $this->seedRoles();
        $reviewer = $this->userWithRole('reviewer');
        $project = $this->submittedProject();

        Sanctum::actingAs($reviewer);

        $review = Review::factory()->create([
            'project_id' => $project->id,
            'reviewer_id' => $reviewer->id,
            'status' => ReviewStatus::Pending,
        ]);

        $this->postJson('/api/reviews/'.$review->id.'/approve', [
            'notes' => 'Dokumen sesuai.',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_other_reviewer_cannot_decide(): void
    {
        $this->seedRoles();
        $reviewer = $this->userWithRole('reviewer');
        $project = $this->submittedProject();

        Sanctum::actingAs($reviewer);

        $review = $this->startReview($reviewer, $project);

        $other = $this->userWithRole('reviewer');
        Sanctum::actingAs($other);

        $this->postJson('/api/reviews/'.$review->id.'/approve', [
            'notes' => 'Dokumen sesuai.',
        ])->assertStatus(403);
    }

    public function test_reviewer_can_comment_on_review(): void
    {
        $this->seedRoles();
        $reviewer = $this->userWithRole('reviewer');
        $project = $this->submittedProject();

        Sanctum::actingAs($reviewer);

        $review = $this->startReview($reviewer, $project);

        $this->postJson('/api/reviews/'.$review->id.'/comment', [
            'notes' => 'Mohon perhatikan lampiran pendukung.',
        ])->assertOk();

        $this->assertDatabaseHas('review_logs', [
            'review_id' => $review->id,
            'action' => ReviewAction::Comment->value,
            'notes' => 'Mohon perhatikan lampiran pendukung.',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => ReviewStatus::UnderReview->value,
        ]);
    }

    public function test_reviewer_can_list_own_reviews(): void
    {
        $this->seedRoles();
        $reviewer = $this->userWithRole('reviewer');
        $other = $this->userWithRole('reviewer');

        Review::factory()->count(2)->create(['reviewer_id' => $reviewer->id]);
        Review::factory()->create(['reviewer_id' => $other->id]);

        Sanctum::actingAs($reviewer);

        $this->getJson('/api/reviews')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_reviewer_can_view_review_detail_with_timeline(): void
    {
        $this->seedRoles();
        $reviewer = $this->userWithRole('reviewer');
        $project = $this->submittedProject();

        Sanctum::actingAs($reviewer);

        $review = $this->startReview($reviewer, $project);

        $this->postJson('/api/reviews/'.$review->id.'/approve', [
            'notes' => 'Dokumen lengkap dan sesuai.',
        ])->assertOk();

        $this->getJson('/api/reviews/'.$review->id)
            ->assertOk()
            ->assertJsonPath('data.status', ReviewStatus::Approved->value)
            ->assertJsonCount(2, 'data.logs')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'project' => ['id', 'project_number', 'title', 'status', 'user'],
                    'reviewer' => ['id', 'name', 'email'],
                    'logs' => [['id', 'action', 'notes', 'reviewer', 'created_at']],
                ],
            ]);
    }
}
