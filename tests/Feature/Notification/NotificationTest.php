<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    private function createNotification(User $user, array $data = [], ?string $readAt = null): void
    {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\ReviewDecisionNotification',
            'data' => $data + [
                'title' => 'Keputusan Review',
                'message' => 'Review project PRJ-2026-00001 disetujui.',
                'action_url' => '/projects/example',
            ],
            'read_at' => $readAt,
        ]);
    }

    public function test_notifications_require_authentication(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
        $this->getJson('/api/notifications/unread-count')->assertStatus(401);
    }

    public function test_user_can_list_own_notifications_with_unread_count(): void
    {
        $this->seedRoles();
        $user = $this->userWithRole('applicant');

        $this->createNotification($user);
        $this->createNotification($user, readAt: now());

        Sanctum::actingAs($user);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('unread_count', 1)
            ->assertJsonStructure([
                'data' => [['id', 'type', 'data', 'read_at', 'created_at']],
                'meta',
                'unread_count',
            ]);
    }

    public function test_user_can_view_unread_count(): void
    {
        $this->seedRoles();
        $user = $this->userWithRole('reviewer');

        $this->createNotification($user);
        $this->createNotification($user);

        Sanctum::actingAs($user);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 2);
    }

    public function test_user_can_mark_single_notification_as_read(): void
    {
        $this->seedRoles();
        $user = $this->userWithRole('applicant');

        $this->createNotification($user);
        $this->createNotification($user);

        $notification = $user->notifications()->first();

        Sanctum::actingAs($user);

        $this->postJson('/api/notifications/'.$notification->id.'/read')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $this->seedRoles();
        $owner = $this->userWithRole('applicant');
        $other = $this->userWithRole('reviewer');

        $this->createNotification($owner);

        $notification = $owner->notifications()->first();

        Sanctum::actingAs($other);

        $this->postJson('/api/notifications/'.$notification->id.'/read')->assertStatus(404);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $this->seedRoles();
        $user = $this->userWithRole('reviewer');

        $this->createNotification($user);
        $this->createNotification($user);

        Sanctum::actingAs($user);

        $this->postJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_user_without_notification_permission_cannot_access_notification_endpoints(): void
    {
        $this->seedRoles();
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/notifications')->assertStatus(403);
        $this->getJson('/api/notifications/unread-count')->assertStatus(403);
        $this->postJson('/api/notifications/read-all')->assertStatus(403);
    }
}
