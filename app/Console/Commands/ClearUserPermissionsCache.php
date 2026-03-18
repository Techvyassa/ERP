<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Control\Organization;
use App\Contracts\DatabaseConnectionRouter;

class ClearUserPermissionsCache extends Command
{
    protected $signature = 'cache:clear-permissions {org_slug?} {user_id?}';
    protected $description = 'Clear RBAC permission cache for users';

    public function __construct(
        private DatabaseConnectionRouter $dbRouter
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $orgSlug = $this->argument('org_slug');
        $userId = $this->argument('user_id');

        // If specific user ID provided, clear only that user
        if ($userId) {
            $cacheKey = "rbac:user:{$userId}:permissions";
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
                $this->info("✓ Cleared cache for user ID: {$userId}");
            } else {
                $this->warn("No cache found for user ID: {$userId}");
            }
            return 0;
        }

        // If org_slug provided, clear all users in that org
        if ($orgSlug) {
            $org = Organization::where('org_slug', $orgSlug)->first();
            
            if (!$org) {
                $this->error("Organization '{$orgSlug}' not found!");
                return 1;
            }

            $this->info("Clearing permission cache for: {$org->org_name}");
            
            // Switch to tenant database
            $this->dbRouter->setTenantConnection($org->org_slug, $org->tenant_db_name);
            
            // Get all users
            $users = DB::connection('tenant')->table('user_master')->get();
            
            $cleared = 0;
            foreach ($users as $user) {
                $cacheKey = "rbac:user:{$user->id}:permissions";
                if (Cache::has($cacheKey)) {
                    Cache::forget($cacheKey);
                    $cleared++;
                }
            }
            
            $this->info("✅ Cleared cache for {$cleared} users");
            return 0;
        }

        // If no arguments, clear all permission caches
        $this->info("Clearing all permission caches...");
        
        // Get all organizations
        $organizations = Organization::whereIn('registration_status', ['ACTIVE', 'PENDING'])->get();
        
        $totalCleared = 0;
        foreach ($organizations as $org) {
            try {
                $this->dbRouter->setTenantConnection($org->org_slug, $org->tenant_db_name);
                
                $users = DB::connection('tenant')->table('user_master')->get();
                
                foreach ($users as $user) {
                    $cacheKey = "rbac:user:{$user->id}:permissions";
                    if (Cache::has($cacheKey)) {
                        Cache::forget($cacheKey);
                        $totalCleared++;
                    }
                }
                
                $this->line("  ✓ {$org->org_slug}: {$users->count()} users");
            } catch (\Exception $e) {
                $this->warn("  ✗ {$org->org_slug}: " . $e->getMessage());
            }
        }
        
        $this->info("\n✅ Cleared cache for {$totalCleared} users across {$organizations->count()} organizations");
        
        return 0;
    }
}
