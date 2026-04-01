<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Role;
use App\Models\Tenant\RolePermission;
use Illuminate\Support\Facades\DB;

class DefaultRolePermissionSeeder extends Seeder
{
    private const MODULES = [
        'ADMIN', 'USER', 'MANAGER', 'SECURITY', 'STORE', 'QC', 'PROCUREMENT', 'PRODUCTION', 'SALES', 'CUSTOMER', 'MAINTENANCE'
    ];

    public function run(): void
    {
        DB::connection('tenant')->transaction(function () {
            $roles = Role::whereIn('role_code', ['ADMIN', 'USER', 'MANAGER', 'SECURITY', 'STORE', 'QC', 'PROCUREMENT', 'PRODUCTION', 'SALES', 'CUSTOMER', 'MAINTENANCE'])->get();

            if ($roles->isEmpty()) {
                echo "✗ Default roles not found. Please run DefaultRoleSeeder first.\n";
                return;
            }

            foreach ($roles as $role) {
                foreach (self::MODULES as $moduleCode) {
                    $permissions = $this->getPermissionsForRole($role->role_code, $moduleCode);

                    RolePermission::updateOrCreate(
                        [
                            'role_id' => $role->id,
                            'module_code' => $moduleCode,
                        ],
                        [
                            'scope' => $permissions['scope'],
                            'view_cross_department' => $permissions['view_cross_department'],
                            'can_view' => $permissions['can_view'],
                            'can_create' => $permissions['can_create'],
                            'can_edit' => $permissions['can_edit'],
                            'can_approve' => $permissions['can_approve'],
                            'can_delete' => $permissions['can_delete'],
                            'created_by' => null,
                        ]
                    );
                }
            }

            echo "✓ Default role permissions seeded successfully\n";
        });
    }

    private function getPermissionsForRole(string $roleCode, string $moduleCode): array
    {
        $can_view = false;
        $can_create = false;
        $can_edit = false;
        $can_approve = false;
        $can_delete = false;
        $scope = 'department';
        $view_cross = false;

        // Admin gets global full access
        if ($roleCode === 'ADMIN') {
            return [
                'scope' => 'global',
                'view_cross_department' => true,
                'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_approve' => true, 'can_delete' => true,
            ];
        }

        // Roles map to their identically named modules (Departmental Roles)
        if ($roleCode === $moduleCode) {
            $can_view = $can_create = $can_edit = $can_approve = true;
        } 
        // Widespread base roles (MANAGER/USER)
        elseif (in_array($roleCode, ['MANAGER', 'USER'])) {
            $can_view = true;
            // Prevent non-admins from editing administration/admin module
            if ($moduleCode !== 'ADMIN') {
                $can_create = $can_edit = true;
                if ($roleCode === 'MANAGER') {
                    $can_approve = true;
                }
            }
        } else {
            // View-only fallback for cross-module visibility
            $can_view = true;
        }

        return [
            'scope' => $scope,
            'view_cross_department' => $view_cross,
            'can_view' => $can_view,
            'can_create' => $can_create,
            'can_edit' => $can_edit,
            'can_approve' => $can_approve,
            'can_delete' => $can_delete,
        ];
    }
}
