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
            ['dept_code' => 'PROD',  'dept_name' => 'Production'],
            ['dept_code' => 'ADMIN', 'dept_name' => 'IT / Admin'],
            ['dept_code' => 'SALES', 'dept_name' => 'Sales'],
            ['dept_code' => 'CUST',  'dept_name' => 'Customer Relations'],
            ['dept_code' => 'MAINT', 'dept_name' => 'Maintenance'],
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
            // Production
            [
                'role_code'     => 'PRODUCTION',
                'role_name'     => 'Production',
                'description'   => 'Production department access',
                'is_system_role'=> true,
            ],
            // Admin
            [
                'role_code'     => 'ADMIN',
                'role_name'     => 'System Administrator',
                'description'   => 'Full system access across all modules',
                'is_system_role'=> true,
            ],
            // Sales
            [
                'role_code'     => 'SALES_EXE',
                'role_name'     => 'Sales Executive',
                'description'   => 'Creates and manages sales orders',
                'is_system_role'=> true,
            ],
            [
                'role_code'     => 'SALES_MGR',
                'role_name'     => 'Sales Manager',
                'description'   => 'Approves sales orders and manages customers',
                'is_system_role'=> true,
            ],
            // Customer Relations
            [
                'role_code'     => 'CUST_EXE',
                'role_name'     => 'Customer Relations Executive',
                'description'   => 'Manages customer accounts and complaints',
                'is_system_role'=> true,
            ],
            // Maintenance
            [
                'role_code'     => 'MAINT_TECH',
                'role_name'     => 'Maintenance Technician',
                'description'   => 'Executes work orders and PM tasks',
                'is_system_role'=> true,
            ],
            [
                'role_code'     => 'MAINT_MGR',
                'role_name'     => 'Maintenance Manager',
                'description'   => 'Approves work orders and manages assets',
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
            'PROD'  => ['PRODUCTION'],
            'ADMIN' => ['ADMIN'],
            'SALES' => ['SALES_EXE', 'SALES_MGR'],
            'CUST'  => ['CUST_EXE'],
            'MAINT' => ['MAINT_TECH', 'MAINT_MGR'],
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
                'BOM'        => [true,  false, false, false, false],
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
                'BOM'        => [true,  false, false, false, false],
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
                'BOM'        => [false, false, false, false, false],
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
                'BOM'        => [false, false, false, false, false],
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
                'BOM'        => [false, false, false, false, false],
                'SALES'      => [true,  false, false, false, false],
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
                'BOM'        => [false, false, false, false, false],
                'SALES'      => [true,  true,  true,  true,  false],
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
                'BOM'        => [false, false, false, false, false],
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
                'BOM'        => [false, false, false, false, false],
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
                'BOM'        => [false, false, false, false, false],
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
                'BOM'        => [false, false, false, false, false],
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
                'BOM'        => [false, false, false, false, false],
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
                'BOM'        => [true,  true,  true,  false, false],
            ],
            // ── Production ────────────────────────────────────────────────
            'PRODUCTION' => [
                'PO'         => [false, false, false, false, false],
                'GATE_ENTRY' => [false, false, false, false, false],
                'MR_GRN'     => [true,  false, false, false, false],
                'QC'         => [false, false, false, false, false],
                'INVOICE'    => [false, false, false, false, false],
                'PAYMENT'    => [false, false, false, false, false],
                'STOCK'      => [true,  false, false, false, false],
                'REPORTS'    => [true,  false, false, false, false],
                'BOM'        => [true,  true,  true,  false, false],
                'PRODUCTION' => [true,  true,  true,  true,  false],
            ],
            // ── Admin ─────────────────────────────────────────────────────
            'ADMIN' => [
                'PO'          => [true, true, true, true, true],
                'GATE_ENTRY'  => [true, true, true, true, true],
                'MR_GRN'      => [true, true, true, true, true],
                'QC'          => [true, true, true, true, true],
                'INVOICE'     => [true, true, true, true, true],
                'PAYMENT'     => [true, true, true, true, true],
                'STOCK'       => [true, true, true, true, true],
                'REPORTS'     => [true, true, true, true, true],
                'BOM'         => [true, true, true, true, true],
                'PRODUCTION'  => [true, true, true, true, true],
                'SALES'       => [true, true, true, true, true],
                'CUSTOMER'    => [true, true, true, true, true],
                'MAINTENANCE' => [true, true, true, true, true],
                'ADMIN'       => [true, true, true, true, true],
            ],
            // ── Sales ─────────────────────────────────────────────────────
            'SALES_EXE' => [
                'SALES'    => [true,  true,  true,  false, false],
                'CUSTOMER' => [true,  false, false, false, false],
                'STOCK'    => [true,  false, false, false, false],
                'REPORTS'  => [false, false, false, false, false],
            ],
            'SALES_MGR' => [
                'SALES'    => [true,  true,  true,  true,  false],
                'CUSTOMER' => [true,  true,  true,  false, false],
                'STOCK'    => [true,  false, false, false, false],
                'REPORTS'  => [true,  false, false, false, false],
            ],
            // ── Customer Relations ─────────────────────────────────────────
            'CUST_EXE' => [
                'CUSTOMER' => [true,  true,  true,  false, false],
                'SALES'    => [true,  false, false, false, false],
                'REPORTS'  => [false, false, false, false, false],
            ],
            // ── Maintenance ────────────────────────────────────────────────
            'MAINT_TECH' => [
                'MAINTENANCE' => [true,  true,  true,  false, false],
                'STOCK'       => [true,  false, false, false, false],
                'REPORTS'     => [false, false, false, false, false],
            ],
            'MAINT_MGR' => [
                'MAINTENANCE' => [true,  true,  true,  true,  false],
                'STOCK'       => [true,  false, false, false, false],
                'REPORTS'     => [true,  false, false, false, false],
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
