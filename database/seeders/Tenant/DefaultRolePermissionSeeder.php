<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Role;
use App\Models\Tenant\RolePermission;
use Illuminate\Support\Facades\DB;

class DefaultRolePermissionSeeder extends Seeder
{
    private const MODULES = [
        'SETTINGS',
        'USERS',
        'PR',
        'PO',
        'ASN',
        'GATE_ENTRY',
        'MR_GRN',
        'GRN',
        'QC',
        'STOCK',
        'INVOICE',
        'PAYMENT',
        'INVENTORY',
        'WAREHOUSE',
        'MATERIAL',
        'USER_MGMT',
        'ROLE_MGMT',
        'DEPT_MGMT',
        'BOM',
        'ADMINISTRATION'
    ];

    public function run(): void
    {
        DB::connection('tenant')->transaction(function () {
            $roles = Role::whereIn('role_code', ['ADMIN', 'MANAGER', 'USER', 'PROCUREMENT', 'SECURITY', 'WAREHOUSE', 'QC'])->get();

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

        if (in_array($roleCode, ['ADMIN'])) {
            $scope = 'global';
            $view_cross = true;
        }

        switch ($roleCode) {
            case 'ADMIN':
                $can_view = $can_create = $can_edit = $can_approve = $can_delete = true;
                break;
            case 'MANAGER':
                if ($moduleCode === 'ADMINISTRATION') {
                    $can_view = true;
                } else {
                    $can_view = $can_create = $can_edit = $can_approve = true;
                }
                break;
            case 'USER':
                if ($moduleCode === 'ADMINISTRATION') {
                    $can_view = true;
                } else {
                    $can_view = $can_create = $can_edit = true;
                }
                break;
            case 'PROCUREMENT':
                if (in_array($moduleCode, ['PR', 'PO', 'ASN', 'VENDORS'])) {
                    $can_view = $can_create = $can_edit = $can_approve = true;
                } else {
                    $can_view = true;
                }
                break;
            case 'SECURITY':
                if (in_array($moduleCode, ['GATE_ENTRY'])) {
                    $can_view = $can_create = $can_edit = true;
                } else {
                    $can_view = true;
                }
                break;
            case 'WAREHOUSE':
                if (in_array($moduleCode, ['INVENTORY', 'WAREHOUSE', 'MR_GRN', 'GRN', 'STOCK', 'MATERIAL'])) {
                    $can_view = $can_create = $can_edit = $can_approve = true;
                } else {
                    $can_view = true;
                }
                break;
            case 'QC':
                if (in_array($moduleCode, ['QC'])) {
                    $can_view = $can_create = $can_edit = $can_approve = true;
                } else {
                    $can_view = true;
                }
                break;
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
