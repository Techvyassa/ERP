<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Control\Organization;
use App\Models\Tenant\Role;
use App\Models\Tenant\RolePermission;
use App\Contracts\DatabaseConnectionRouter;

class AddASNPermissions extends Command
{
    protected $signature = 'tenant:add-asn-permissions {org_slug}';
    protected $description = 'Add ASN module permissions to all roles for a tenant';

    public function __construct(
        private DatabaseConnectionRouter $dbRouter
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $orgSlug = $this->argument('org_slug');

        // Switch to control database
        $this->dbRouter->switchToControl();

        // Find organization
        $organization = Organization::where('org_slug', $orgSlug)->first();

        if (!$organization) {
            $this->error("Organization not found: {$orgSlug}");
            return 1;
        }

        if (!$organization->tenant_db_name) {
            $this->error("Organization does not have a tenant database configured.");
            return 1;
        }

        $this->info("Organization: {$organization->org_name}");
        $this->info("Database: {$organization->tenant_db_name}");
        $this->newLine();

        try {
            // Switch to tenant database
            $this->dbRouter->switchToTenant($organization->tenant_db_name);

            // Define ASN permissions for each role
            $permissions = [
                'ADMIN' => [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_approve' => true,
                    'can_delete' => true,
                ],
                'PROC_EXE' => [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_approve' => false,
                    'can_delete' => false,
                ],
                'PROC_MGR' => [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_approve' => true,
                    'can_delete' => true,
                ],
                'STOREKEEPER' => [
                    'can_view' => true,
                    'can_create' => false,
                    'can_edit' => false,
                    'can_approve' => false,
                    'can_delete' => false,
                ],
                'STORE_MGR' => [
                    'can_view' => true,
                    'can_create' => false,
                    'can_edit' => true,
                    'can_approve' => true,
                    'can_delete' => false,
                ],
            ];

            foreach ($permissions as $roleCode => $perms) {
                $role = Role::where('role_code', $roleCode)->first();

                if (!$role) {
                    $this->warn("  Role {$roleCode} not found, skipping...");
                    continue;
                }

                RolePermission::updateOrCreate(
                    [
                        'role_id' => $role->id,
                        'module_code' => 'ASN',
                    ],
                    $perms
                );

                $this->info("  ✓ {$roleCode} - ASN permissions added");
            }

            $this->newLine();
            $this->info("✅ ASN permissions added successfully!");

            // Display the permissions
            $this->newLine();
            $this->info("Current ASN Permissions:");
            $this->table(
                ['Role', 'View', 'Create', 'Edit', 'Approve', 'Delete'],
                RolePermission::where('module_code', 'ASN')
                    ->join('role_master', 'role_permissions.role_id', '=', 'role_master.id')
                    ->select([
                        'role_master.role_code',
                        'role_permissions.can_view',
                        'role_permissions.can_create',
                        'role_permissions.can_edit',
                        'role_permissions.can_approve',
                        'role_permissions.can_delete',
                    ])
                    ->get()
                    ->map(fn($p) => [
                        $p->role_code,
                        $p->can_view ? '✓' : '✗',
                        $p->can_create ? '✓' : '✗',
                        $p->can_edit ? '✓' : '✗',
                        $p->can_approve ? '✓' : '✗',
                        $p->can_delete ? '✓' : '✗',
                    ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to add ASN permissions: {$e->getMessage()}");
            return 1;
        }
    }
}
