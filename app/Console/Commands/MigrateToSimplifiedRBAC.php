<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateToSimplifiedRBAC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:migrate-simplified 
                            {--tenant-db= : Tenant database name (e.g., tenant_abc123)}
                            {--dry-run : Preview changes without applying}
                            {--backup : Create backup before migration}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate from specialized roles to simplified departmental RBAC structure';

    /**
     * Role mapping from old to new structure
     */
    private const ROLE_MAPPING = [
        // Old Procurement roles → New PROCUREMENT role
        'PROC_EXE' => 'PROCUREMENT',
        'PROC_MGR' => 'PROCUREMENT',
        
        // Old Security roles → New SECURITY role
        'SECURITY_GUARD' => 'SECURITY',
        'SECURITY_SUPVR' => 'SECURITY',
        
        // Old Warehouse roles → New STORE role
        'STOREKEEPER' => 'STORE',
        'STORE_MGR' => 'STORE',
        
        // Old QC roles → New QC role
        'QC_TECH' => 'QC',
        'QC_MGR' => 'QC',
        
        // Old Finance roles → New roles (MANAGER/USER)
        'AP_CLERK' => 'USER',
        'FIN_MGR' => 'MANAGER',
        'CFO' => 'MANAGER',
        
        // Old PPC role → New role
        'PPC_USER' => 'VIEWER',
        
        // Production stays same
        'PRODUCTION' => 'PRODUCTION',
        
        // Sales roles → New SALES role
        'SALES_EXE' => 'SALES',
        'SALES_MGR' => 'SALES',
        
        // Customer role → New CUSTOMER role
        'CUST_EXE' => 'CUSTOMER',
        
        // Maintenance roles → New MAINTENANCE role
        'MAINT_TECH' => 'MAINTENANCE',
        'MAINT_MGR' => 'MAINTENANCE',
        
        // Admin stays same
        'ADMIN' => 'ADMIN',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting RBAC Migration to Simplified Structure');
        $this->newLine();

        // Get tenant database name
        $tenantDb = $this->option('tenant-db');
        
        if (!$tenantDb) {
            // Try to get from environment
            $tenantDb = env('TENANT_DB_DATABASE');
            
            if (!$tenantDb) {
                $this->error('❌ No tenant database specified!');
                $this->newLine();
                $this->info('Please specify tenant database using one of these methods:');
                $this->info('1. Command option: --tenant-db=your_tenant_db_name');
                $this->info('2. Environment variable: TENANT_DB_DATABASE in .env file');
                $this->newLine();
                $this->info('Example:');
                $this->info('  php artisan rbac:migrate-simplified --tenant-db=tenant_abc123');
                $this->newLine();
                
                // List available tenant databases
                $this->listTenantDatabases();
                
                return Command::FAILURE;
            }
            
            $this->info("Using tenant database from .env: {$tenantDb}");
        } else {
            $this->info("Target tenant database: {$tenantDb}");
        }

        // Configure tenant database connection
        $this->configureTenantConnection($tenantDb);

        // Confirmation
        if (!$this->option('force')) {
            if (!$this->confirm('This will modify roles and user assignments. Continue?', false)) {
                $this->info('❌ Migration cancelled.');
                return Command::FAILURE;
            }
        }

        // Dry run mode
        if ($this->option('dry-run')) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
            $this->newLine();
            $this->previewChanges();
            return Command::SUCCESS;
        }

        try {
            DB::connection('tenant')->beginTransaction();

            // Step 1: Create new roles if they don't exist
            $this->info('Step 1: Creating simplified roles...');
            $newRoleIds = $this->createNewRoles();

            // Step 2: Map users from old roles to new roles
            $this->info('Step 2: Migrating user role assignments...');
            $userMigrationCount = $this->migrateUserRoles();

            // Step 3: Update dept_role_map
            $this->info('Step 3: Updating department-role mappings...');
            $this->updateDeptRoleMap();

            // Step 4: Update role_permissions
            $this->info('Step 4: Regenerating role permissions...');
            $this->regeneratePermissions();

            // Step 5: Deactivate old specialized roles
            $this->info('Step 5: Deactivating old specialized roles...');
            $this->deactivateOldRoles();

            DB::connection('tenant')->commit();

            $this->newLine();
            $this->info('✅ RBAC Migration completed successfully!');
            $this->info("   - Users migrated: {$userMigrationCount}");
            $this->info("   - New roles created: " . count($newRoleIds));
            $this->newLine();
            $this->warn('⚠️  Please test the system and verify user permissions.');
            $this->warn('⚠️  Old roles are deactivated (not deleted) for safety.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            
            $this->error('❌ Migration failed: ' . $e->getMessage());
            $this->error('   Transaction rolled back. No changes were made.');
            Log::error('RBAC Migration failed', ['exception' => $e]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Configure tenant database connection
     */
    private function configureTenantConnection(string $tenantDb): void
    {
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Test connection
        try {
            DB::connection('tenant')->getPdo();
            $this->info("✅ Connected to tenant database: {$tenantDb}");
        } catch (\Exception $e) {
            $this->error("❌ Failed to connect to tenant database: {$tenantDb}");
            $this->error('   Error: ' . $e->getMessage());
            exit(1);
        }
    }

    /**
     * List available tenant databases
     */
    private function listTenantDatabases(): void
    {
        try {
            // Query control database for tenant list
            $tenants = DB::connection('control')
                ->table('organizations')
                ->select('org_id', 'org_name', 'org_slug', 'tenant_db_name', 'registration_status as status')
                ->whereIn('registration_status', ['ACTIVE', 'SUSPENDED'])
                ->orderBy('org_name')
                ->get();

            if ($tenants->isEmpty()) {
                $this->warn('No active tenants found in control database.');
                return;
            }

            $this->info('Available tenant databases:');
            $this->newLine();

            $rows = $tenants->map(fn($t) => [
                $t->org_id,
                $t->org_name,
                $t->org_slug,
                $t->tenant_db_name,
                $t->status
            ])->toArray();

            $this->table(
                ['ID', 'Organization', 'Slug', 'Database Name', 'Status'],
                $rows
            );

            $this->newLine();
            $this->info('Use: php artisan rbac:migrate-simplified --tenant-db=DATABASE_NAME');

        } catch (\Exception $e) {
            $this->warn('Could not retrieve tenant list: ' . $e->getMessage());
        }
    }

    /**
     * Preview changes without applying
     */
    private function previewChanges(): void
    {
        $this->info('📊 CURRENT STATE:');
        $this->table(
            ['Role Code', 'User Count'],
            $this->getCurrentRoleStats()
        );

        $this->newLine();
        $this->info('📋 PROPOSED MIGRATIONS:');
        
        $rows = [];
        foreach (self::ROLE_MAPPING as $oldRole => $newRole) {
            $userCount = DB::connection('tenant')
                ->table('users')
                ->join('role_master', 'users.role_id', '=', 'role_master.id')
                ->where('role_master.role_code', $oldRole)
                ->where('users.is_active', true)
                ->count();

            if ($userCount > 0) {
                $rows[] = [
                    "{$oldRole} → {$newRole}",
                    "{$userCount} users"
                ];
            }
        }

        if (empty($rows)) {
            $this->info('   No users found with old roles. Migration is safe.');
        } else {
            $this->table(['Migration Path', 'Affected Users'], $rows);
        }
    }

    /**
     * Get current role statistics
     */
    private function getCurrentRoleStats(): array
    {
        $stats = DB::connection('tenant')
            ->table('users')
            ->join('role_master', 'users.role_id', '=', 'role_master.id')
            ->select('role_master.role_code', DB::raw('COUNT(*) as user_count'))
            ->where('users.is_active', true)
            ->groupBy('role_master.role_code')
            ->orderBy('role_master.role_code')
            ->get();

        return $stats->map(fn($row) => [$row->role_code, $row->user_count])->toArray();
    }

    /**
     * Create new simplified roles
     */
    private function createNewRoles(): array
    {
        $simplifiedRoles = [
            ['role_code' => 'ADMIN', 'role_name' => 'System Administrator', 'description' => 'Full system access across all modules'],
            ['role_code' => 'MANAGER', 'role_name' => 'Manager', 'description' => 'Department-level management and approvals'],
            ['role_code' => 'USER', 'role_name' => 'User', 'description' => 'Standard operational access'],
            ['role_code' => 'VIEWER', 'role_name' => 'Viewer', 'description' => 'Read-only access'],
            ['role_code' => 'SECURITY', 'role_name' => 'Security', 'description' => 'Gate entry, visitor, and asset security'],
            ['role_code' => 'STORE', 'role_name' => 'Store', 'description' => 'Inventory and warehouse operations'],
            ['role_code' => 'QC', 'role_name' => 'Quality Control', 'description' => 'Quality inspection and compliance'],
            ['role_code' => 'PROCUREMENT', 'role_name' => 'Procurement', 'description' => 'Vendor management and purchasing operations'],
            ['role_code' => 'PRODUCTION', 'role_name' => 'Production', 'description' => 'Production planning and shop floor operations'],
            ['role_code' => 'SALES', 'role_name' => 'Sales', 'description' => 'Sales, orders, and customer handling'],
            ['role_code' => 'CUSTOMER', 'role_name' => 'Customer', 'description' => 'Customer portal and self-service access'],
            ['role_code' => 'MAINTENANCE', 'role_name' => 'Maintenance', 'description' => 'Equipment maintenance and repair operations'],
        ];

        $roleIds = [];

        foreach ($simplifiedRoles as $roleData) {
            $role = DB::connection('tenant')
                ->table('role_master')
                ->updateOrInsert(
                    ['role_code' => $roleData['role_code']],
                    array_merge($roleData, [
                        'is_active' => true,
                        'is_system_role' => true,
                        'created_at' => now(),
                    ])
                );

            $roleId = DB::connection('tenant')
                ->table('role_master')
                ->where('role_code', $roleData['role_code'])
                ->value('id');

            $roleIds[$roleData['role_code']] = $roleId;
            $this->line("   ✓ {$roleData['role_code']} (ID: {$roleId})");
        }

        return $roleIds;
    }

    /**
     * Migrate users from old roles to new roles
     */
    private function migrateUserRoles(): int
    {
        $migratedCount = 0;

        foreach (self::ROLE_MAPPING as $oldRoleCode => $newRoleCode) {
            // Get new role ID
            $newRoleId = DB::connection('tenant')
                ->table('role_master')
                ->where('role_code', $newRoleCode)
                ->value('id');

            if (!$newRoleId) {
                $this->warn("   ⚠️  New role {$newRoleCode} not found, skipping...");
                continue;
            }

            // Find users with old role
            $usersToUpdate = DB::connection('tenant')
                ->table('users')
                ->join('role_master', 'users.role_id', '=', 'role_master.id')
                ->where('role_master.role_code', $oldRoleCode)
                ->select('users.id', 'users.email', 'users.dept_id')
                ->get();

            foreach ($usersToUpdate as $user) {
                // Validate that new role is valid for user's department
                $isValidMapping = DB::connection('tenant')
                    ->table('dept_role_map')
                    ->where('dept_id', $user->dept_id)
                    ->where('role_id', $newRoleId)
                    ->exists();

                if (!$isValidMapping) {
                    // Try to find ROOT department for global roles
                    $globalRoles = ['ADMIN', 'MANAGER', 'USER', 'VIEWER'];
                    if (in_array($newRoleCode, $globalRoles)) {
                        $rootDept = DB::connection('tenant')
                            ->table('department_master')
                            ->where('dept_code', 'ROOT')
                            ->first();

                        if ($rootDept) {
                            // Update user's department to ROOT for global roles
                            DB::connection('tenant')
                                ->table('users')
                                ->where('id', $user->id)
                                ->update([
                                    'dept_id' => $rootDept->id,
                                    'role_id' => $newRoleId,
                                ]);
                            
                            $this->line("   ✓ User {$user->email} → {$newRoleCode} (dept: ROOT)");
                            $migratedCount++;
                        }
                    } else {
                        $this->warn("   ⚠️  Cannot map user {$user->email}: Role {$newRoleCode} not valid for their department");
                    }
                } else {
                    // Safe to update
                    DB::connection('tenant')
                        ->table('users')
                        ->where('id', $user->id)
                        ->update(['role_id' => $newRoleId]);

                    $this->line("   ✓ User {$user->email} → {$newRoleCode}");
                    $migratedCount++;
                }
            }
        }

        return $migratedCount;
    }

    /**
     * Update department-role mappings
     */
    private function updateDeptRoleMap(): void
    {
        $mappings = [
            // Global roles → ROOT department
            'ADMIN' => 'ROOT',
            'MANAGER' => 'ROOT',
            'USER' => 'ROOT',
            'VIEWER' => 'ROOT',
            
            // Department-specific roles
            'SECURITY' => 'SECURITY',
            'STORE' => 'STORE',
            'QC' => 'QC',
            'PROCUREMENT' => 'PROCUREMENT',
            'PRODUCTION' => 'PROD',
            'SALES' => 'SALES',
            'CUSTOMER' => 'CUSTOMER',
            'MAINTENANCE' => 'MAINT',
        ];

        foreach ($mappings as $roleCode => $deptCode) {
            $roleId = DB::connection('tenant')
                ->table('role_master')
                ->where('role_code', $roleCode)
                ->value('id');

            $deptId = DB::connection('tenant')
                ->table('department_master')
                ->where('dept_code', $deptCode)
                ->value('id');

            if ($roleId && $deptId) {
                DB::connection('tenant')
                    ->table('dept_role_map')
                    ->updateOrInsert(
                        ['dept_id' => $deptId, 'role_id' => $roleId],
                        ['created_at' => now()]
                    );
                
                $this->line("   ✓ Mapped {$roleCode} → {$deptCode}");
            }
        }
    }

    /**
     * Regenerate permissions for all roles
     */
    private function regeneratePermissions(): void
    {
        // Call the seeder programmatically
        $seeder = new \Database\Seeders\Tenant\DefaultRolePermissionSeeder();
        $seeder->setCommand($this);
        $seeder->run();
    }

    /**
     * Deactivate old specialized roles (don't delete!)
     */
    private function deactivateOldRoles(): void
    {
        $oldRoles = array_keys(self::ROLE_MAPPING);
        
        // Keep these roles active
        $keepActive = ['ADMIN', 'PRODUCTION'];

        $rolesToDeactivate = array_diff($oldRoles, $keepActive);

        foreach ($rolesToDeactivate as $roleCode) {
            // Check if any active users still have this role
            $activeUsers = DB::connection('tenant')
                ->table('users')
                ->join('role_master', 'users.role_id', '=', 'role_master.id')
                ->where('role_master.role_code', $roleCode)
                ->where('users.is_active', true)
                ->count();

            if ($activeUsers === 0) {
                DB::connection('tenant')
                    ->table('role_master')
                    ->where('role_code', $roleCode)
                    ->update(['is_active' => false]);
                
                $this->line("   ✓ Deactivated role: {$roleCode}");
            } else {
                $this->warn("   ⚠️  Skipped deactivation of {$roleCode}: {$activeUsers} active users still assigned");
            }
        }
    }
}
