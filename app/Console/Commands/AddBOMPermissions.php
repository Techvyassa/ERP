<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;
use App\Contracts\DatabaseConnectionRouter;

class AddBOMPermissions extends Command
{
    protected $signature = 'tenant:add-bom-permissions {org_slug}';
    protected $description = 'Add BOM module permissions to all roles for a specific tenant';

    public function __construct(
        private DatabaseConnectionRouter $dbRouter
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $orgSlug = $this->argument('org_slug');
        
        // Find organization
        $org = Organization::where('org_slug', $orgSlug)->first();
        
        if (!$org) {
            $this->error("Organization '{$orgSlug}' not found!");
            return 1;
        }
        
        $this->info("Adding BOM permissions for organization: {$org->org_name}");
        
        // Switch to tenant database
        $this->dbRouter->setTenantConnection($org->org_slug, $org->db_name);
        
        try {
            // Define BOM permissions for each role
            $rolePermissions = [
                'ADMIN'           => [true, true, true, true, true],   // Full access
                'PROC_EXE'        => [true, false, false, false, false], // View only
                'PROC_MGR'        => [true, false, false, false, false], // View only
                'SECURITY_GUARD'  => [false, false, false, false, false], // No access
                'SECURITY_SUPVR'  => [false, false, false, false, false], // No access
                'STOREKEEPER'     => [false, false, false, false, false], // No access
                'STORE_MGR'       => [false, false, false, false, false], // No access
                'QC_TECH'         => [false, false, false, false, false], // No access
                'QC_MGR'          => [false, false, false, false, false], // No access
                'AP_CLERK'        => [false, false, false, false, false], // No access
                'FIN_MGR'         => [false, false, false, false, false], // No access
                'CFO'             => [false, false, false, false, false], // No access
                'PPC_USER'        => [true, true, true, false, false],   // View, Create, Edit
            ];
            
            $addedCount = 0;
            $updatedCount = 0;
            
            foreach ($rolePermissions as $roleCode => $permissions) {
                // Get role
                $role = DB::connection('tenant')
                    ->table('role_master')
                    ->where('role_code', $roleCode)
                    ->first();
                
                if (!$role) {
                    $this->warn("  Role {$roleCode} not found, skipping...");
                    continue;
                }
                
                // Check if permission already exists
                $existing = DB::connection('tenant')
                    ->table('role_permissions')
                    ->where('role_id', $role->id)
                    ->where('module_code', 'BOM')
                    ->first();
                
                if ($existing) {
                    // Update existing permission
                    DB::connection('tenant')
                        ->table('role_permissions')
                        ->where('role_id', $role->id)
                        ->where('module_code', 'BOM')
                        ->update([
                            'can_view'    => $permissions[0],
                            'can_create'  => $permissions[1],
                            'can_edit'    => $permissions[2],
                            'can_approve' => $permissions[3],
                            'can_delete'  => $permissions[4],
                        ]);
                    $updatedCount++;
                    $this->line("  ✓ Updated BOM permissions for {$roleCode}");
                } else {
                    // Insert new permission
                    DB::connection('tenant')
                        ->table('role_permissions')
                        ->insert([
                            'role_id'     => $role->id,
                            'module_code' => 'BOM',
                            'can_view'    => $permissions[0],
                            'can_create'  => $permissions[1],
                            'can_edit'    => $permissions[2],
                            'can_approve' => $permissions[3],
                            'can_delete'  => $permissions[4],
                            'created_by'  => null,
                        ]);
                    $addedCount++;
                    $this->line("  ✓ Added BOM permissions for {$roleCode}");
                }
            }
            
            $this->info("\n✅ BOM permissions processed successfully!");
            $this->info("   Added: {$addedCount}");
            $this->info("   Updated: {$updatedCount}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Failed to add BOM permissions: " . $e->getMessage());
            return 1;
        }
    }
}
