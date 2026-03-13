<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant\Role;
use App\Models\Tenant\RolePermission;
use App\Contracts\DatabaseConnectionRouter;

class GrantInventoryPermissions extends Command
{
    protected $signature = 'tenant:grant-inventory-permissions 
                            {tenant_db : The tenant database name}
                            {role_code=ADMIN : The role code to grant permissions to (default: ADMIN)}';

    protected $description = 'Grant INVENTORY module permissions to a role';

    protected DatabaseConnectionRouter $dbRouter;

    public function __construct(DatabaseConnectionRouter $dbRouter)
    {
        parent::__construct();
        $this->dbRouter = $dbRouter;
    }

    public function handle()
    {
        $tenantDb = $this->argument('tenant_db');
        $roleCode = $this->argument('role_code');

        try {
            // Switch to tenant database
            $this->dbRouter->switchToTenant($tenantDb);
            $this->info("Switched to tenant database: {$tenantDb}");

            // Find the role
            $role = Role::where('role_code', $roleCode)->first();
            
            if (!$role) {
                $this->error("Role '{$roleCode}' not found!");
                return 1;
            }

            $this->info("Found role: {$role->role_name} (ID: {$role->id})");

            // Grant INVENTORY permissions
            $permission = RolePermission::updateOrCreate(
                [
                    'role_id' => $role->id,
                    'module_code' => 'INVENTORY'
                ],
                [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_approve' => true,
                    'can_delete' => true,
                ]
            );

            $this->info("✓ INVENTORY permissions granted to {$role->role_name}");
            $this->info("  - can_view: true");
            $this->info("  - can_create: true");
            $this->info("  - can_edit: true");
            $this->info("  - can_approve: true");
            $this->info("  - can_delete: true");

            // Clear permission cache
            \Illuminate\Support\Facades\Cache::flush();
            $this->info("✓ Permission cache cleared");

            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
