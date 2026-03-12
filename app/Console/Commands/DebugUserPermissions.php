<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Control\Organization;
use App\Models\Tenant\User;
use App\Models\Tenant\RolePermission;
use App\Contracts\DatabaseConnectionRouter;
use Illuminate\Support\Facades\Cache;

class DebugUserPermissions extends Command
{
    protected $signature = 'debug:user-permissions {org_slug} {user_id}';
    protected $description = 'Debug user permissions for troubleshooting';

    public function __construct(
        private DatabaseConnectionRouter $dbRouter
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $orgSlug = $this->argument('org_slug');
        $userId = $this->argument('user_id');

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

            // Get user
            $user = User::with(['role'])->find($userId);

            if (!$user) {
                $this->error("User not found: {$userId}");
                return 1;
            }

            $this->info("User: {$user->email}");
            $this->info("Role: {$user->role->role_code} - {$user->role->role_name}");
            $this->newLine();

            // Check cache
            $cacheKey = "rbac:user:{$userId}:permissions";
            $cachedPerms = Cache::get($cacheKey);

            if ($cachedPerms) {
                $this->warn("⚠ Permissions are CACHED");
                $this->info("Cache Key: {$cacheKey}");
                $this->newLine();
                $this->info("Cached Permissions:");
                foreach ($cachedPerms as $module => $perms) {
                    $this->line("  {$module}: " . json_encode($perms));
                }
            } else {
                $this->info("✓ No cached permissions (will load from database)");
            }

            $this->newLine();

            // Get permissions from database
            $permissions = RolePermission::where('role_id', $user->role_id)->get();

            $this->info("Database Permissions ({$permissions->count()} modules):");
            $this->table(
                ['Module', 'View', 'Create', 'Edit', 'Approve', 'Delete'],
                $permissions->map(fn($p) => [
                    $p->module_code,
                    $p->can_view ? '✓' : '✗',
                    $p->can_create ? '✓' : '✗',
                    $p->can_edit ? '✓' : '✗',
                    $p->can_approve ? '✓' : '✗',
                    $p->can_delete ? '✓' : '✗',
                ])
            );

            // Check specifically for ASN
            $asnPerm = $permissions->where('module_code', 'ASN')->first();
            $this->newLine();
            if ($asnPerm) {
                $this->info("✓ ASN module permission EXISTS");
                $this->info("  View: " . ($asnPerm->can_view ? 'YES' : 'NO'));
                $this->info("  Create: " . ($asnPerm->can_create ? 'YES' : 'NO'));
            } else {
                $this->error("✗ ASN module permission NOT FOUND");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed: {$e->getMessage()}");
            return 1;
        }
    }
}
