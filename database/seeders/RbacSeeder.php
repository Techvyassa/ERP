<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RbacSeeder extends Seeder
{
    /**
     * Seed all ERP departments, roles, dept_role_map, and role_permissions.
     * Safe to re-run — uses updateOrCreate / insertOrIgnore.
     */
    public function run(): void
    {
        $this->command->info('Seeding Departments...');
        $this->seedDepartments();

        $this->command->info('Seeding Roles...');
        $this->seedRoles();

        $this->command->info('Seeding Dept-Role Map...');
        $this->seedDeptRoleMap();

        $this->command->info('Seeding Role Permissions...');
        $this->seedRolePermissions();

        $this->command->info('✅ RBAC Seeding complete.');
    }

    // ─────────────────────────────────────────────
    //  1. DEPARTMENTS
    // ─────────────────────────────────────────────
    private function seedDepartments(): void
    {
        $departments = [
            ['dept_code' => 'PROC',  'dept_name' => 'Procurement'],
            ['dept_code' => 'SEC',   'dept_name' => 'Security'],
            ['dept_code' => 'STORE', 'dept_name' => 'Warehouse / Store'],
            ['dept_code' => 'QC',    'dept_name' => 'Quality Control'],
            ['dept_code' => 'FIN',   'dept_name' => 'Finance'],
            ['dept_code' => 'PPC',   'dept_name' => 'Production Planning & Control'],
            ['dept_code' => 'ADMIN', 'dept_name' => 'IT / Admin'],
        ];

        foreach ($departments as $dept) {
            DB::connection('tenant')
                ->table('department_master')
                ->updateOrInsert(
                    ['dept_code' => $dept['dept_code']],
                    array_merge($dept, [
                        'is_active'  => true,
                        'created_at' => now(),
                    ])
                );
        }
    }

    // ─────────────────────────────────────────────
    //  2. ROLES  (13 roles from Section 9.1)
    // ─────────────────────────────────────────────
    private function seedRoles(): void
    {
        $roles = [
            // Procurement
            [
                'role_code'     => 'PROC_EXE',
                'role_name'     => 'Procurement Executive',
                'description'   => 'Creates Purchase Orders and manages vendors',
                'is_system_role'=> true,
            ],
            [
                'role_code'     => 'PROC_MGR',
                'role_name'     => 'Procurement Manager',
                'description'   => 'Approves POs and PO Amendments',
                'is_system_role'=> true,
            ],
            // Security
            [
                'role_code'     => 'SECURITY_GUARD',
                'role_name'     => 'Security Guard',
                'description'   => 'Creates Gate Entries',
                'is_system_role'=> true,
            ],
            [
                'role_code'     => 'SECURITY_SUPVR',
                'role_name'     => 'Security Supervisor',
                'description'   => 'Performs Gate Verification',
                'is_system_role'=> true,
            ],
            // Warehouse
            [
                'role_code'     => 'STOREKEEPER',
                'role_name'     => 'Storekeeper',
                'description'   => 'Creates MR, GRN, and Putaway tasks',
                'is_system_role'=> true,
            ],
            [
                'role_code'     => 'STORE_MGR',
                'role_name'     => 'Store Manager',
                'description'   => 'Approves GRN and manages bin locations',
                'is_system_role'=> true,
            ],
            // Quality
            [
                'role_code'     => 'QC_TECH',
                'role_name'     => 'QC Technician',
                'description'   => 'Records QC test results',
                'is_system_role'=> true,
            ],
            [
                'role_code'     => 'QC_MGR',
                'role_name'     => 'QC Manager',
                'description'   => 'Issues Usage Decisions on inspection lots',
                'is_system_role'=> true,
            ],
            // Finance
            [
                'role_code'     => 'AP_CLERK',
                'role_name'     => 'Accounts Payable Clerk',
                'description'   => 'Registers and verifies vendor invoices',
                'is_system_role'=> true,
            ],
            [
                'role_code'     => 'FIN_MGR',
                'role_name'     => 'Finance Manager',
                'description'   => 'Approves payment proposals',
                'is_system_role'=> true,
            ],
            [
                'role_code'     => 'CFO',
                'role_name'     => 'Chief Financial Officer',
                'description'   => 'Approves high-value payments',
                'is_system_role'=> true,
            ],
            // PPC
            [
                'role_code'     => 'PPC_USER',
                'role_name'     => 'PPC User',
                'description'   => 'Read-only access to stock levels and GRN data',
                'is_system_role'=> true,
            ],
            // Admin
            [
                'role_code'     => 'ADMIN',
                'role_name'     => 'System Administrator',
                'description'   => 'Full system access across all modules',
                'is_system_role'=> true,
            ],
        ];

        foreach ($roles as $role) {
            DB::connection('tenant')
                ->table('role_master')
                ->updateOrInsert(
                    ['role_code' => $role['role_code']],
                    array_merge($role, [
                        'is_active'  => true,
                        'created_at' => now(),
                    ])
                );
        }
    }

    // ─────────────────────────────────────────────
    //  3. DEPT-ROLE MAP
    //     Defines which roles are valid per dept
    // ─────────────────────────────────────────────
    private function seedDeptRoleMap(): void
    {
        // dept_code => [role_codes that belong to it]
        $map = [
            'PROC'  => ['PROC_EXE', 'PROC_MGR'],
            'SEC'   => ['SECURITY_GUARD', 'SECURITY_SUPVR'],
            'STORE' => ['STOREKEEPER', 'STORE_MGR'],
            'QC'    => ['QC_TECH', 'QC_MGR'],
            'FIN'   => ['AP_CLERK', 'FIN_MGR', 'CFO'],
            'PPC'   => ['PPC_USER'],
            'ADMIN' => ['ADMIN'],
        ];

        foreach ($map as $deptCode => $roleCodes) {
            $dept = DB::connection('tenant')
                ->table('department_master')
                ->where('dept_code', $deptCode)
                ->first();

            if (!$dept) {
                $this->command->warn("  Department {$deptCode} not found, skipping...");
                continue;
            }

            foreach ($roleCodes as $roleCode) {
                $role = DB::connection('tenant')
                    ->table('role_master')
                    ->where('role_code', $roleCode)
                    ->first();

                if (!$role) {
                    $this->command->warn("  Role {$roleCode} not found, skipping...");
                    continue;
                }

                DB::connection('tenant')
                    ->table('dept_role_map')
                    ->updateOrInsert(
                        ['dept_id' => $dept->id, 'role_id' => $role->id],
                        ['created_at' => now()]
                    );
            }
        }
    }

    // ─────────────────────────────────────────────
    //  4. ROLE PERMISSIONS
    //     Based on Section 9.2 permission matrix
    // ─────────────────────────────────────────────
    private function seedRolePermissions(): void
    {
        /*
         * Matrix key:  role_code => [module_code => [view, create, edit, approve, delete]]
         *
         * Modules:
         *   PO          - Purchase Orders
         *   GATE_ENTRY  - Gate Entries & Verifications
         *   MR_GRN      - Material Receipts & GRN
         *   QC          - Quality Control (results + decision)
         *   INVOICE     - Vendor Invoices
         *   PAYMENT     - Payments
         *   STOCK       - Stock / Inventory View
         *   REPORTS     - Reports
         */
        $permMatrix = [
            // ── Procurement ──────────────────────────────────────────────
            'PROC_EXE' => [
                'PO'         => [true,  true,  true,  false, false],
                'GATE_ENTRY' => [false, false, false, false, false],
                'MR_GRN'     => [false, false, false, false, false],
                'QC'         => [false, false, false, false, false],
                'INVOICE'    => [false, false, false, false, false],
                'PAYMENT'    => [false, false, false, false, false],
                'STOCK'      => [true,  false, false, false, false],
                'REPORTS'    => [false, false, false, false, false],
            ],
            'PROC_MGR' => [
                'PO'         => [true,  true,  true,  true,  false],
                'GATE_ENTRY' => [false, false, false, false, false],
                'MR_GRN'     => [false, false, false, false, false],
                'QC'         => [false, false, false, false, false],
                'INVOICE'    => [false, false, false, false, false],
                'PAYMENT'    => [false, false, false, false, false],
                'STOCK'      => [true,  false, false, false, false],
                'REPORTS'    => [true,  false, false, false, false],
            ],
            // ── Security ─────────────────────────────────────────────────
            'SECURITY_GUARD' => [
                'PO'         => [false, false, false, false, false],
                'GATE_ENTRY' => [true,  true,  true,  false, false],
                'MR_GRN'     => [false, false, false, false, false],
                'QC'         => [false, false, false, false, false],
                'INVOICE'    => [false, false, false, false, false],
                'PAYMENT'    => [false, false, false, false, false],
                'STOCK'      => [false, false, false, false, false],
                'REPORTS'    => [false, false, false, false, false],
            ],
            'SECURITY_SUPVR' => [
                'PO'         => [false, false, false, false, false],
                'GATE_ENTRY' => [true,  true,  true,  true,  false],
                'MR_GRN'     => [false, false, false, false, false],
                'QC'         => [false, false, false, false, false],
                'INVOICE'    => [false, false, false, false, false],
                'PAYMENT'    => [false, false, false, false, false],
                'STOCK'      => [false, false, false, false, false],
                'REPORTS'    => [false, false, false, false, false],
            ],
            // ── Warehouse ─────────────────────────────────────────────────
            'STOREKEEPER' => [
                'PO'         => [false, false, false, false, false],
                'GATE_ENTRY' => [false, false, false, false, false],
                'MR_GRN'     => [true,  true,  true,  false, false],
                'QC'         => [false, false, false, false, false],
                'INVOICE'    => [false, false, false, false, false],
                'PAYMENT'    => [false, false, false, false, false],
                'STOCK'      => [true,  false, false, false, false],
                'REPORTS'    => [false, false, false, false, false],
            ],
            'STORE_MGR' => [
                'PO'         => [false, false, false, false, false],
                'GATE_ENTRY' => [false, false, false, false, false],
                'MR_GRN'     => [true,  true,  true,  true,  false],
                'QC'         => [false, false, false, false, false],
                'INVOICE'    => [false, false, false, false, false],
                'PAYMENT'    => [false, false, false, false, false],
                'STOCK'      => [true,  false, false, false, false],
                'REPORTS'    => [true,  false, false, false, false],
            ],
            // ── Quality ───────────────────────────────────────────────────
            'QC_TECH' => [
                'PO'         => [false, false, false, false, false],
                'GATE_ENTRY' => [false, false, false, false, false],
                'MR_GRN'     => [false, false, false, false, false],
                'QC'         => [true,  true,  true,  false, false],
                'INVOICE'    => [false, false, false, false, false],
                'PAYMENT'    => [false, false, false, false, false],
                'STOCK'      => [true,  false, false, false, false],
                'REPORTS'    => [false, false, false, false, false],
            ],
            'QC_MGR' => [
                'PO'         => [false, false, false, false, false],
                'GATE_ENTRY' => [false, false, false, false, false],
                'MR_GRN'     => [false, false, false, false, false],
                'QC'         => [true,  true,  true,  true,  false],
                'INVOICE'    => [false, false, false, false, false],
                'PAYMENT'    => [false, false, false, false, false],
                'STOCK'      => [true,  false, false, false, false],
                'REPORTS'    => [true,  false, false, false, false],
            ],
            // ── Finance ───────────────────────────────────────────────────
            'AP_CLERK' => [
                'PO'         => [false, false, false, false, false],
                'GATE_ENTRY' => [false, false, false, false, false],
                'MR_GRN'     => [false, false, false, false, false],
                'QC'         => [false, false, false, false, false],
                'INVOICE'    => [true,  true,  true,  false, false],
                'PAYMENT'    => [false, false, false, false, false],
                'STOCK'      => [true,  false, false, false, false],
                'REPORTS'    => [false, false, false, false, false],
            ],
            'FIN_MGR' => [
                'PO'         => [false, false, false, false, false],
                'GATE_ENTRY' => [false, false, false, false, false],
                'MR_GRN'     => [false, false, false, false, false],
                'QC'         => [false, false, false, false, false],
                'INVOICE'    => [true,  false, false, false, false],
                'PAYMENT'    => [true,  true,  true,  true,  false],
                'STOCK'      => [true,  false, false, false, false],
                'REPORTS'    => [true,  false, false, false, false],
            ],
            'CFO' => [
                'PO'         => [false, false, false, false, false],
                'GATE_ENTRY' => [false, false, false, false, false],
                'MR_GRN'     => [false, false, false, false, false],
                'QC'         => [false, false, false, false, false],
                'INVOICE'    => [true,  false, false, false, false],
                'PAYMENT'    => [true,  true,  true,  true,  false],
                'STOCK'      => [true,  false, false, false, false],
                'REPORTS'    => [true,  false, false, false, false],
            ],
            // ── PPC ───────────────────────────────────────────────────────
            'PPC_USER' => [
                'PO'         => [false, false, false, false, false],
                'GATE_ENTRY' => [false, false, false, false, false],
                'MR_GRN'     => [true,  false, false, false, false],
                'QC'         => [false, false, false, false, false],
                'INVOICE'    => [false, false, false, false, false],
                'PAYMENT'    => [false, false, false, false, false],
                'STOCK'      => [true,  false, false, false, false],
                'REPORTS'    => [true,  false, false, false, false],
            ],
            // ── Admin ─────────────────────────────────────────────────────
            'ADMIN' => [
                'PO'         => [true, true, true, true, true],
                'GATE_ENTRY' => [true, true, true, true, true],
                'MR_GRN'     => [true, true, true, true, true],
                'QC'         => [true, true, true, true, true],
                'INVOICE'    => [true, true, true, true, true],
                'PAYMENT'    => [true, true, true, true, true],
                'STOCK'      => [true, true, true, true, true],
                'REPORTS'    => [true, true, true, true, true],
            ],
        ];

        foreach ($permMatrix as $roleCode => $modules) {
            $role = DB::connection('tenant')
                ->table('role_master')
                ->where('role_code', $roleCode)
                ->first();

            if (!$role) {
                $this->command->warn("  Role {$roleCode} not found, skipping permissions...");
                continue;
            }

            foreach ($modules as $moduleCode => $flags) {
                DB::connection('tenant')
                    ->table('role_permissions')
                    ->updateOrInsert(
                        ['role_id' => $role->id, 'module_code' => $moduleCode],
                        [
                            'can_view'    => $flags[0],
                            'can_create'  => $flags[1],
                            'can_edit'    => $flags[2],
                            'can_approve' => $flags[3],
                            'can_delete'  => $flags[4],
                        ]
                    );
            }

            $this->command->line("  ✓ {$roleCode} — " . count($modules) . " module permissions set");
        }
    }
}
