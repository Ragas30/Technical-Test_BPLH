<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

class PermissionSeeder extends Seeder
{
    /**
     * Permission matrix grouped by role.
     *
     * @var array<string, array<int, Permission>>
     */
    private array $permissionMatrix = [
        Role::Reviewer->value => [
            Permission::DashboardView,
            Permission::ProjectViewAny,
            Permission::ProjectView,
            Permission::DocumentDownload,
            Permission::ReviewViewAny,
            Permission::ReviewView,
            Permission::ReviewStart,
            Permission::ReviewApprove,
            Permission::ReviewReject,
            Permission::ReviewRevision,
            Permission::ReviewComment,
            Permission::ActivityView,
            Permission::NotificationView,
            Permission::ExportExcel,
            Permission::ExportPdf,
        ],
        Role::Applicant->value => [
            Permission::DashboardView,
            Permission::ProjectView,
            Permission::ProjectCreate,
            Permission::ProjectUpdate,
            Permission::ProjectDelete,
            Permission::ProjectSubmit,
            Permission::DocumentUpload,
            Permission::DocumentDownload,
            Permission::DocumentDelete,
            Permission::ActivityView,
            Permission::NotificationView,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allPermissions = collect(Permission::cases())->map(fn (Permission $permission) => $permission->value);

        $allPermissions->each(fn (string $permission) => PermissionModel::updateOrCreate(
            ['name' => $permission],
            ['guard_name' => 'web']
        ));

        $matrix = $this->permissionMatrix + [
            Role::Admin->value => $allPermissions->all(),
        ];

        foreach ($matrix as $role => $permissions) {
            RoleModel::findByName($role)->syncPermissions($permissions);
        }
    }
}
