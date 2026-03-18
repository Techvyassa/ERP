<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Role;
use App\Models\Tenant\RolePermission;
use Illuminate\Support\Facades\DB;

class DefaultRolePermissionSeeder extends Seeder
{
    /**
     * All module codes in the system
     */
    private const MODULES = [
        'SETTINGS',     // Settings (Roles, Departments, HSN, GST, Currency)
        'USERS',        // User Management
        'PR',           // Purchase Requisition
        'PO',           // Purchase Order
        'ASN',          // Advance Shipping Notice
        'GATE_ENTRY',   // Gate Entry
        'MR_GRN',       // Material Receipt & GRN
        'GRN',          // Goods Receipt Note
        'QC',           // Quality Control
        'STOCK',        // Stock/Putaway Management
        'INVOICE',      // Invoice Management
        'PAYMENT',      // Payment Management
        'INVENTORY',    // Inventory Management
        'WAREHOUSE',    // Warehouse Management
        'MATERIAL',     // Material Management
        'USER_MGMT',    // User Management
        'ROLE_MGMT',    // Role Management
        'DEPT_MGMT',    // Department Management
        'BOM',          // Bill of Materials
    ];

    /**
     * Run the database seeds.
     * Creates role permissions for all default roles
     * 
     * ADMIN: all permissions true for all modules
     * VIEWER: only can_view true for all modules
     * 
     * Requirements: 17.1-17.5
     */
    public function run(): void
    {
        // Use Tenant database connection
        DB::connection('tenant')->transaction(function () {
            // Get all roles
            $adminRole = Role::where('role_code', 'ADMIN')->first();
            $managerRole = Role::where('role_code', 'MANAGER')->first();
            $userRole = Role::where('role_code', 'USER')->first();
            $viewerRole = Role::where('role_code', 'VIEWER')->first();

            if (!$adminRole || !$managerRole || !$userRole || !$viewerRole) {
                echo "✗ Default roles not found. Please run DefaultRoleSeeder first.\n";
                return;
            }

            // Seed permissions for each role
            $this->seedAdminPermissions($adminRole);
            $this->seedManagerPermissions($managerRole);
            $this->seedUserPermissions($userRole);
            $this->seedViewerPermissions($viewerRole);

            echo "✓ Default role permissions seeded successfully\n";
        });
    }

    /**
     * Seed ADMIN permissions - all permissions true for all modules
     */
    private function seedAdminPermissions(Role $role): void
    {
        foreach (self::MODULES as $moduleCode) {
            RolePermission::updateOrCreate(
                [
                    'role_id' => $role->id,
                    'module_code' => $moduleCode,
                ],
                [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_approve' => true,
                    'can_delete' => true,
                    'created_by' => null,
                ]
            );
        }
    }

    /**
     * Seed MANAGER permissions - view, create, edit, approve for all modules
     */
    private function seedManagerPermissions(Role $role): void
    {
        foreach (self::MODULES as $moduleCode) {
            RolePermission::updateOrCreate(
                [
                    'role_id' => $role->id,
                    'module_code' => $moduleCode,
                ],
                [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_approve' => true,
                    'can_delete' => false,
                    'created_by' => null,
                ]
            );
        }
    }

    /**
     * Seed USER permissions - view, create, edit for all modules
     */
    private function seedUserPermissions(Role $role): void
    {
        foreach (self::MODULES as $moduleCode) {
            RolePermission::updateOrCreate(
                [
                    'role_id' => $role->id,
                    'module_code' => $moduleCode,
                ],
                [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_approve' => false,
                    'can_delete' => false,
                    'created_by' => null,
                ]
            );
        }
    }

    /**
     * Seed VIEWER permissions - only can_view true for all modules
     */
    private function seedViewerPermissions(Role $role): void
    {
        foreach (self::MODULES as $moduleCode) {
            RolePermission::updateOrCreate(
                [
                    'role_id' => $role->id,
                    'module_code' => $moduleCode,
                ],
                [
                    'can_view' => true,
                    'can_create' => false,
                    'can_edit' => false,
                    'can_approve' => false,
                    'can_delete' => false,
                    'created_by' => null,
                ]
            );
        }
    }
}
