<?php

namespace App\Console\Commands;

use App\Contracts\TenantProvisioningService;
use App\Models\Control\Organization;
use Illuminate\Console\Command;

class TenantProvision extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:provision {org_slug : The organization slug to provision}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually provision a tenant database for an organization';

    /**
     * Execute the console command.
     */
    public function handle(TenantProvisioningService $provisioningService): int
    {
        $orgSlug = $this->argument('org_slug');
        
        $this->info("Starting tenant provisioning for organization: {$orgSlug}");
        
        // Find organization by slug
        $organization = Organization::where('org_slug', $orgSlug)->first();
        
        if (!$organization) {
            $this->error("Organization not found: {$orgSlug}");
            return Command::FAILURE;
        }
        
        $this->info("Found organization: {$organization->org_name} (ID: {$organization->org_id})");
        $this->info("Current status: {$organization->registration_status}");
        
        // Check if already provisioned
        if ($organization->registration_status === 'ACTIVE' && $organization->tenant_db_name) {
            $this->warn("Organization is already provisioned with database: {$organization->tenant_db_name}");
            
            if (!$this->confirm('Do you want to re-provision? This will NOT delete existing data.')) {
                $this->info('Provisioning cancelled.');
                return Command::SUCCESS;
            }
        }
        
        // Provision tenant
        $this->info('Provisioning tenant database...');
        $this->newLine();
        
        $result = $provisioningService->provisionTenant($organization->org_id);
        
        if ($result->success) {
            $this->newLine();
            $this->info('✓ Tenant provisioning completed successfully!');
            $this->info("Database: {$result->tenantDbName}");
            $this->newLine();
            
            $this->info('Completed steps:');
            foreach ($result->steps as $step) {
                $this->line("  • {$step}");
            }
            
            return Command::SUCCESS;
        } else {
            $this->newLine();
            $this->error('✗ Tenant provisioning failed!');
            $this->error("Error: {$result->errorMessage}");
            $this->newLine();
            
            if (!empty($result->steps)) {
                $this->warn('Completed steps before failure:');
                foreach ($result->steps as $step) {
                    $this->line("  • {$step}");
                }
            }
            
            return Command::FAILURE;
        }
    }
}
