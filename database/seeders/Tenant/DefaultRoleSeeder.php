<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\Role;
use Illuminate\Support\Facades\DB;

class DefaultRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates default roles: ADMIN, MANAGER, USER, VIEWER
     * 
     * Requirements: 17.1-17.5
     */
    public function run(): void
    {
        DB::connection('tenant')->transaction(function () {
            // Standardized Roles
            $roles = [
                ['role_code' => 'ADMIN',       'role_name' => 'System Administrator', 'description' => 'Full system access across all modules'],
                ['role_code' => 'MANAGER',     'role_name' => 'Manager',             'description' => 'Department-level management and approvals'],
                ['role_code' => 'USER',        'role_name' => 'User',                'description' => 'Standard operational access'],
                ['role_code' => 'VIEWER',      'role_name' => 'Viewer',              'description' => 'Read-only access'],
                ['role_code' => 'QC',          'role_name' => 'Quality Control',     'description' => 'Quality inspection and compliance'],
                ['role_code' => 'PROCUREMENT', 'role_name' => 'Procurement',         'description' => 'Vendor management and purchasing operations'],
                ['role_code' => 'STORE',       'role_name' => 'Store',               'description' => 'Inventory and warehouse operations'],
                ['role_code' => 'SALES',       'role_name' => 'Sales',               'description' => 'Sales, orders, and customer handling'],
                ['role_code' => 'SECURITY',    'role_name' => 'Security',            'description' => 'Gate entry, visitor, and asset security'],
                ['role_code' => 'MAINTENANCE', 'role_name' => 'Maintenance',         'description' => 'Equipment maintenance and repair operations'],
                ['role_code' => 'CUSTOMER',    'role_name' => 'Customer',            'description' => 'Customer portal and self-service access'],
                ['role_code' => 'PRODUCTION',  'role_name' => 'Production',          'description' => 'Production planning and shop floor operations'],
            ];

            $seededRoles = [];
            foreach ($roles as $roleData) {
                $seededRoles[$roleData['role_code']] = Role::updateOrCreate(
                    ['role_code' => $roleData['role_code']],
                    array_merge($roleData, ['is_active' => true, 'is_system_role' => true])
                );
            }
            echo "✓ Roles seeded successfully\n";

            // Standardized Departments
            echo "Seeding/Updating departments...\n";
            $rootDept = \App\Models\Tenant\Department::whereNull('parent_dept_id')->first();
            if (!$rootDept) {
                $rootDept = \App\Models\Tenant\Department::create([
                    'dept_code' => 'ROOT',
                    'dept_name' => 'Organization',
                    'is_active' => true,
                ]);
            }

            $departments = [
                ['code' => 'PROD',        'name' => 'Production',               'cost_center' => 'PROD-001'],
                ['code' => 'STORE',       'name' => 'Warehouse / Store',        'cost_center' => 'STORE-001'],
                ['code' => 'QC',          'name' => 'Quality Control',          'cost_center' => 'QC-001'],
                ['code' => 'PROCUREMENT', 'name' => 'Procurement',              'cost_center' => 'PRC-001'],
                ['code' => 'SALES',       'name' => 'Sales',                    'cost_center' => 'SALES-001'],
                ['code' => 'SECURITY',    'name' => 'Security',                 'cost_center' => 'SEC-001'],
                ['code' => 'MAINT',       'name' => 'Maintenance',              'cost_center' => 'MNT-001'],
                ['code' => 'CRM',         'name' => 'Customer Relations (CRM)', 'cost_center' => 'CRM-001'],
                ['code' => 'CUSTOMER',    'name' => 'Customer Portal',          'cost_center' => null],
            ];

            foreach ($departments as $deptData) {
                \App\Models\Tenant\Department::updateOrCreate(
                    ['dept_code' => $deptData['code']],
                    [
                        'dept_name' => $deptData['name'],
                        'cost_center_code' => $deptData['cost_center'],
                        'parent_dept_id' => $rootDept->id,
                        'is_active' => true,
                    ]
                );
            }
            echo "✓ Departments seeded successfully\n";

            // Role-Department Mappings
            echo "Updating department-role mappings...\n";
            
            // Global Roles -> ROOT
            $globalRoles = ['ADMIN', 'MANAGER', 'USER', 'VIEWER'];
            foreach ($globalRoles as $roleCode) {
                if (isset($seededRoles[$roleCode])) {
                    DB::connection('tenant')->table('dept_role_map')->updateOrInsert(
                        ['dept_id' => $rootDept->id, 'role_id' => $seededRoles[$roleCode]->id],
                        ['created_at' => now()]
                    );
                }
            }

            // Departmental Roles
            $mappings = [
                'SECURITY'    => 'SECURITY',
                'STORE'       => 'STORE',
                'QC'          => 'QC',
                'PROCUREMENT' => 'PROCUREMENT',
                'PRODUCTION'  => 'PROD',
                'SALES'       => 'SALES',
                'CUSTOMER'    => 'CUSTOMER',
                'MAINTENANCE' => 'MAINT',
            ];

            foreach ($mappings as $roleCode => $deptCode) {
                $role = $seededRoles[$roleCode] ?? null;
                $deptRecord = DB::connection('tenant')->table('department_master')
                    ->where('dept_code', '=', $deptCode)->first();

                if ($role && $deptRecord) {
                    DB::connection('tenant')->table('dept_role_map')->updateOrInsert(
                        ['dept_id' => $deptRecord->id, 'role_id' => $role->id],
                        ['created_at' => now()]
                    );
                    echo "  - Mapped {$roleCode} to department {$deptCode}\n";
                }
            }
        });
    }
}
