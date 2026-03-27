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
        // Use Tenant database connection
        DB::connection('tenant')->transaction(function () {
            $roles = [
                [
                    'role_code' => 'ADMIN',
                    'role_name' => 'Administration',
                    'description' => 'Full administration access',
                    'is_active' => true,
                    'is_system_role' => true,
                    'created_by' => null,
                ],
                [
                    'role_code' => 'USER',
                    'role_name' => 'User',
                    'description' => 'Standard user access',
                    'is_active' => true,
                    'is_system_role' => true,
                    'created_by' => null,
                ],
                [
                    'role_code' => 'MANAGER',
                    'role_name' => 'Manager',
                    'description' => 'Management level access',
                    'is_active' => true,
                    'is_system_role' => true,
                    'created_by' => null,
                ],
                [
                    'role_code' => 'PROCUREMENT',
                    'role_name' => 'Procurement',
                    'description' => 'Procurement department access',
                    'is_active' => true,
                    'is_system_role' => true,
                    'created_by' => null,
                ],
                [
                    'role_code' => 'SECURITY',
                    'role_name' => 'Security',
                    'description' => 'Security and gate entry access',
                    'is_active' => true,
                    'is_system_role' => true,
                    'created_by' => null,
                ],
                [
                    'role_code' => 'STORE',
                    'role_name' => 'Store',
                    'description' => 'Warehouse and store operations',
                    'is_active' => true,
                    'is_system_role' => true,
                    'created_by' => null,
                ],
                [
                    'role_code' => 'QC',
                    'role_name' => 'Quality Control',
                    'description' => 'Quality control operations',
                    'is_active' => true,
                    'is_system_role' => true,
                    'created_by' => null,
                ],
                [
                    'role_code' => 'PRODUCTION',
                    'role_name' => 'Production',
                    'description' => 'Production department access',
                    'is_active' => true,
                    'is_system_role' => true,
                    'created_by' => null,
                ],
            ];

            foreach ($roles as $roleData) {
                // Use updateOrCreate for idempotency
                Role::updateOrCreate(
                    ['role_code' => $roleData['role_code']],
                    $roleData
                );
            }

            echo "✓ Default roles seeded successfully\n";

            // Seed Dept-Role mappings
            echo "Seeding department-role mappings...\n";
            $mappings = [
                'ADMIN'       => 'Administration',
                'SECURITY'    => 'Security',
                'STORE'       => 'Store',
                'QC'          => 'Quality Control',
                'PROCUREMENT' => 'Procurement',
                'PRODUCTION'  => 'Production',
            ];

            foreach ($mappings as $roleCode => $deptName) {
                $role = Role::where('role_code', '=', $roleCode)->first();
                $deptRecord = DB::connection('tenant')->table('department_master')
                    ->where('dept_name', '=', $deptName)
                    ->orWhere('dept_code', '=', $roleCode)
                    ->first();

                if ($role && $deptRecord) {
                    DB::connection('tenant')->table('dept_role_map')->updateOrInsert(
                        ['dept_id' => $deptRecord->id, 'role_id' => $role->id],
                        ['created_at' => now()]
                    );
                    echo "  - Mapped role {$roleCode} to department " . ($deptRecord->dept_name ?? $deptName) . "\n";
                }
            }
        });
    }
}
