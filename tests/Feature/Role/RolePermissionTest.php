<?php

namespace Tests\Feature\Role;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
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

    public function test_roles_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/roles')->assertStatus(401);
        $this->getJson('/api/permissions')->assertStatus(401);
    }

    public function test_admin_can_list_roles_with_permissions(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('admin'));

        $this->getJson('/api/roles')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'permissions',
                ]],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_list_all_permissions(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('admin'));

        $permissionCount = Permission::query()->count();

        $this->getJson('/api/permissions')
            ->assertOk()
            ->assertJsonCount($permissionCount, 'data')
            ->assertJsonStructure(['data' => [['id', 'name']]]);
    }

    public function test_non_admin_cannot_list_roles_or_permissions(): void
    {
        $this->seedRoles();
        Sanctum::actingAs($this->userWithRole('reviewer'));

        $this->getJson('/api/roles')->assertStatus(403);
        $this->getJson('/api/permissions')->assertStatus(403);
    }

    public function test_seeded_admin_role_has_all_permissions(): void
    {
        $this->seedRoles();

        $admin = Role::findByName('admin');

        $this->assertEqualsCanonicalizing(
            Permission::query()->pluck('name')->all(),
            $admin->permissions()->pluck('name')->all(),
        );
    }
}
