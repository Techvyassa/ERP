<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Role;
use App\Models\Tenant\Department;

class RbacSeeder extends Seeder
{
    /**
     * Updated RBAC Seeder to match simplified departmental architecture.
     * Removes legacy specialized roles (PROC_EXE, STOREKEEPER, etc.) 
     * and consolidates into departmental silos.
     */
    public function run(): void
    {
        DB::connection('tenant')->transaction(function () {
            $this->command->info('Cleaning up legacy RBAC data...');
            $this->cleanupLegacy();

            $this->command->info('Syncing default roles...');
            $this->call(DefaultRoleSeeder::class);

            $this->command->info('Syncing default permissions...');
            $this->call(DefaultRolePermissionSeeder::class);
        });
    }

    /**
     * Remove old specialized roles and departments that are no longer part of the target architecture.
     */
    private function cleanupLegacy(): void
    {
        $legacyRoles = [
            'STOR', 'PROC_EXE', 'PROC_MGR', 'SECURITY_GUARD', 'SECURITY_SUPVR', 
            'STOREKEEPER', 'STORE_MGR', 'QC_TECH', 'QC_MGR', 'AP_CLERK', 
            'FIN_MGR', 'CFO', 'PPC_USER', 'PURC'
        ];

        $legacyDepts = ['FIN', 'PPC', 'ADMIN']; // ADMIN as IT/Admin is replaced by Administration

        // 1. Delete legacy role permissions and mappings
        $roleIds = DB::connection('tenant')->table('role_master')
            ->whereIn('role_code', $legacyRoles)
            ->pluck('id');

        if ($roleIds->isNotEmpty()) {
            DB::connection('tenant')->table('role_permissions')->whereIn('role_id', $roleIds)->delete();
            DB::connection('tenant')->table('dept_role_map')->whereIn('role_id', $roleIds)->delete();
            DB::connection('tenant')->table('role_master')->whereIn('id', $roleIds)->delete();
            $this->command->info('  - Removed ' . $roleIds->count() . ' legacy roles.');
        }

        // 2. Delete legacy departments (if empty of users)
        foreach ($legacyDepts as $deptCode) {
            $dept = DB::connection('tenant')->table('department_master')
                ->where('dept_code', $deptCode)
                ->first();

            if ($dept) {
                // Check if any users are assigned
                $userCount = DB::connection('tenant')->table('users')->where('dept_id', $dept->id)->count();
                if ($userCount === 0) {
                    DB::connection('tenant')->table('dept_role_map')->where('dept_id', $dept->id)->delete();
                    DB::connection('tenant')->table('department_master')->where('id', $dept->id)->delete();
                    $this->command->info("  - Removed legacy department: {$deptCode}");
                } else {
                    $this->command->warn("  ! Department {$deptCode} has active users, skipping deletion.");
                }
            }
        }
    }
}
