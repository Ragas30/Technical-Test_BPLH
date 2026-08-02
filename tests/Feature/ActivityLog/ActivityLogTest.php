<?php

namespace Tests\Feature\ActivityLog;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityLogTest extends TestCase
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

    private function makeLog(User $user, ?string $projectId = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user->id,
            'project_id' => $projectId,
            'action' => ActivityAction::ProjectCreated,
            'description' => 'Project dibuat.',
            'properties' => ['project_number' => 'PRJ-2026-00001'],
        ]);
    }

    public function test_activity_logs_require_authentication(): void
    {
        $this->getJson('/api/activity-logs')->assertStatus(401);
        $this->getJson('/api/activity-logs/mine')->assertStatus(401);
    }

    public function test_admin_can_list_all_activity_logs_with_pagination(): void
    {
        $this->seedRoles();
        $admin = $this->userWithRole('admin');
        $other = User::factory()->create();

        ActivityLog::factory()->count(2)->create(['user_id' => $other->id]);
        ActivityLog::factory()->count(3)->create(['user_id' => $admin->id]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/activity-logs')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_admin_can_list_activity_logs_using_cursor_pagination(): void
    {
        $this->seedRoles();
        $admin = $this->userWithRole('admin');

        for ($i = 0; $i < 5; $i++) {
            ActivityLog::create([
                'user_id' => $admin->id,
                'action' => ActivityAction::ProjectCreated,
                'description' => 'Log ke-'.$i,
                'properties' => ['project_number' => 'PRJ-2026-0000'.$i],
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $cursor = ActivityLog::query()->latest('created_at')->cursorPaginate(2)->nextCursor();
        $this->assertNotNull($cursor);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/activity-logs?per_page=2&cursor='.$cursor->encode())
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $meta = $response->json('meta');
        $this->assertArrayHasKey('next_cursor', $meta);
        $this->assertArrayHasKey('prev_cursor', $meta);
    }

    public function test_applicant_can_list_only_own_activity_logs(): void
    {
        $this->seedRoles();
        $applicant = $this->userWithRole('applicant');
        $other = User::factory()->create();

        ActivityLog::factory()->count(2)->create(['user_id' => $applicant->id]);
        ActivityLog::factory()->count(3)->create(['user_id' => $other->id]);

        Sanctum::actingAs($applicant);

        $this->getJson('/api/activity-logs/mine')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.user.id', $applicant->id);
    }

    public function test_applicant_cannot_list_all_activity_logs(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('applicant'));

        $this->getJson('/api/activity-logs')->assertStatus(403);
    }

    public function test_applicant_can_list_own_logs_using_cursor_pagination(): void
    {
        $this->seedRoles();
        $applicant = $this->userWithRole('applicant');

        for ($i = 0; $i < 5; $i++) {
            ActivityLog::create([
                'user_id' => $applicant->id,
                'action' => ActivityAction::ProjectCreated,
                'description' => 'Log ke-'.$i,
                'properties' => ['project_number' => 'PRJ-2026-0000'.$i],
                'created_at' => now()->subMinutes($i),
            ]);
        }

        $cursor = ActivityLog::query()->where('user_id', $applicant->id)->latest('created_at')->cursorPaginate(2)->nextCursor();
        $this->assertNotNull($cursor);

        Sanctum::actingAs($applicant);

        $this->getJson('/api/activity-logs/mine?per_page=2&cursor='.$cursor->encode())
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.user.id', $applicant->id);
    }
}
