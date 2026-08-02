<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

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
        $user = User::factory()->create();

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
        $user = User::factory()->create();

        $this->createNotification($user);
        $this->createNotification($user);

        Sanctum::actingAs($user);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 2);
    }

    public function test_user_can_mark_single_notification_as_read(): void
    {
        $user = User::factory()->create();

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
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->createNotification($owner);

        $notification = $owner->notifications()->first();

        Sanctum::actingAs($other);

        $this->postJson('/api/notifications/'.$notification->id.'/read')->assertStatus(404);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();

        $this->createNotification($user);
        $this->createNotification($user);

        Sanctum::actingAs($user);

        $this->postJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
