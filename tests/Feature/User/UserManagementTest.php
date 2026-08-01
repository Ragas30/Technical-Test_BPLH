<?php

namespace Tests\Feature\User;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_admin_can_list_users_with_pagination(): void
    {
        Sanctum::actingAs($this->admin());

        User::factory()->count(5)->create();

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(6, 'data');
    }

    public function test_admin_can_search_users(): void
    {
        Sanctum::actingAs($this->admin());

        User::factory()->create(['name' => 'Ahmad Fauzi']);
        User::factory()->count(3)->create();

        $this->getJson('/api/users?search=ahmad')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ahmad Fauzi');
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        Sanctum::actingAs($this->admin());

        $applicant = User::factory()->create();
        $applicant->assignRole('applicant');
        User::factory()->count(2)->create();

        $this->getJson('/api/users?role=applicant')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $applicant->id);
    }

    public function test_admin_can_filter_users_by_active_status(): void
    {
        Sanctum::actingAs($this->admin());

        $inactive = User::factory()->create(['is_active' => false]);

        $this->getJson('/api/users?is_active=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inactive->id);
    }

    public function test_admin_can_sort_users(): void
    {
        $admin = $this->admin();
        $admin->update(['name' => 'AAA Admin']);

        Sanctum::actingAs($admin);

        User::factory()->create(['name' => 'Budi']);
        User::factory()->create(['name' => 'Andi']);

        $this->getJson('/api/users?sort_by=name&sort_dir=asc')
            ->assertOk()
            ->assertJsonPath('data.1.name', 'Andi');
    }

    public function test_admin_can_create_user(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'is_active' => true,
            'roles' => ['applicant'],
        ])->assertStatus(201)
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonPath('data.roles.0', 'applicant');

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_create_user_requires_valid_role(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'roles' => ['nonexistent-role'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('roles.0');
    }

    public function test_admin_can_view_user(): void
    {
        Sanctum::actingAs($this->admin());

        $user = User::factory()->create();
        $user->assignRole('applicant');

        $this->getJson("/api/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_admin_can_update_user(): void
    {
        Sanctum::actingAs($this->admin());

        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}", [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'is_active' => true,
            'roles' => ['reviewer'],
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated User')
            ->assertJsonPath('data.roles.0', 'reviewer');

        $this->assertTrue($user->fresh()->hasRole('reviewer'));
    }

    public function test_admin_can_soft_delete_user_and_revoke_tokens(): void
    {
        Sanctum::actingAs($this->admin());

        $user = User::factory()->create();
        $user->createToken('auth-token');

        $this->deleteJson("/api/users/{$user->id}")
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_can_restore_user(): void
    {
        Sanctum::actingAs($this->admin());

        $user = User::factory()->create();
        $user->delete();

        $this->postJson("/api/users/{$user->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_admin_can_list_users_including_trashed(): void
    {
        Sanctum::actingAs($this->admin());

        $user = User::factory()->create();
        $user->delete();

        $this->getJson('/api/users?with_trashed=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_assign_roles(): void
    {
        Sanctum::actingAs($this->admin());

        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}/roles", [
            'roles' => ['applicant'],
        ])->assertOk()
            ->assertJsonPath('data.roles.0', 'applicant');

        $this->assertTrue($user->fresh()->hasRole('applicant'));
    }

    public function test_admin_can_assign_permissions(): void
    {
        Sanctum::actingAs($this->admin());

        $user = User::factory()->create();

        $this->putJson("/api/users/{$user->id}/permissions", [
            'permissions' => ['dashboard.view', 'project.create'],
        ])->assertOk();

        $this->assertTrue($user->fresh()->hasDirectPermission('dashboard.view'));
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $this->seed(RoleSeeder::class);

        $applicant = User::factory()->create();
        $applicant->assignRole('applicant');

        Sanctum::actingAs($applicant);

        $this->getJson('/api/users')->assertStatus(403);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->admin();

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/users/{$admin->id}")->assertStatus(422);
        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = $this->admin();

        Sanctum::actingAs($admin);

        $this->putJson("/api/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'is_active' => false,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('is_active');
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $admin = $this->admin();

        Sanctum::actingAs($admin);

        $this->putJson("/api/users/{$admin->id}/roles", [
            'roles' => ['applicant'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('roles');
    }
}
