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
                    'role_name' => 'Administrator',
                    'description' => 'Full system access with all permissions',
                    'is_active' => true,
                    'is_system_role' => true,
                    'created_by' => null,
                ],
                [
                    'role_code' => 'MANAGER',
                    'role_name' => 'Manager',
                    'description' => 'Management level access with approval permissions',
                    'is_active' => true,
                    'is_system_role' => true,
                    'created_by' => null,
                ],
                [
                    'role_code' => 'USER',
                    'role_name' => 'User',
                    'description' => 'Standard user with create and edit permissions',
                    'is_active' => true,
                    'is_system_role' => true,
                    'created_by' => null,
                ],
                [
                    'role_code' => 'VIEWER',
                    'role_name' => 'Viewer',
                    'description' => 'Read-only access to view data',
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
        });
    }
}
