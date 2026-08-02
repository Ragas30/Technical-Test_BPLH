<?php

namespace Tests\Feature\Seeder;

use App\Enums\Permission;
use App\Enums\ProjectStatus;
use App\Enums\ReviewStatus;
use App\Enums\Role;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as PermissionModel;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_roles_permissions_and_default_users(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@docflow.test')->first();
        $reviewer = User::where('email', 'reviewer@docflow.test')->first();
        $applicant = User::where('email', 'applicant@docflow.test')->first();

        $this->assertNotNull($admin);
        $this->assertNotNull($reviewer);
        $this->assertNotNull($applicant);

        $this->assertTrue($admin->hasRole(Role::Admin->value));
        $this->assertTrue($reviewer->hasRole(Role::Reviewer->value));
        $this->assertTrue($applicant->hasRole(Role::Applicant->value));

        $this->assertSame(count(Role::cases()), \Spatie\Permission\Models\Role::count());
        $this->assertSame(count(Permission::cases()), PermissionModel::count());
        $this->assertCount(count(Permission::cases()), $admin->getAllPermissions());
    }

    public function test_database_seeder_creates_demo_project_and_review_distribution(): void
    {
        $this->seed(DatabaseSeeder::class);

        $applicant = User::where('email', 'applicant@docflow.test')->firstOrFail();
        $reviewer = User::where('email', 'reviewer@docflow.test')->firstOrFail();

        $this->assertSame(12, Project::where('user_id', $applicant->id)->count());
        $this->assertSame(4, Project::where('status', ProjectStatus::Approved->value)->count());
        $this->assertSame(2, Project::where('status', ProjectStatus::Rejected->value)->count());
        $this->assertSame(3, Project::where('status', ProjectStatus::Submitted->value)->count());
        $this->assertSame(3, Project::where('status', ProjectStatus::Draft->value)->count());

        $this->assertSame(9, Review::count());
        $this->assertSame(4, Review::where('status', ReviewStatus::Approved->value)->count());
        $this->assertSame(2, Review::where('status', ReviewStatus::Rejected->value)->count());
        $this->assertSame(3, Review::where('status', ReviewStatus::Pending->value)->count());
        $this->assertSame(9, Review::where('reviewer_id', $reviewer->id)->count());
    }
}
