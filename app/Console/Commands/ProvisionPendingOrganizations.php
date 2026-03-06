<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Control\Organization;
use App\Contracts\TenantProvisioningService;

class ProvisionPendingOrganizations extends Command
{
    protected $signature = 'tenant:provision-pending';
    protected $description = 'Provision all pending organizations';

    public function __construct(
        private TenantProvisioningService $provisioningService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $pendingOrgs = Organization::where('registration_status', 'PENDING')->get();
        
        if ($pendingOrgs->isEmpty()) {
            $this->info('No pending organizations found.');
            return 0;
        }
        
        $this->info("Found {$pendingOrgs->count()} pending organizations.");
        
        foreach ($pendingOrgs as $org) {
            $this->info("Provisioning: {$org->org_name} ({$org->org_slug})");
            
            try {
                $result = $this->provisioningService->provisionTenant($org->org_id, [
                    'first_name' => 'Admin',
                    'last_name' => 'User',
                    'email' => $org->primary_email,
                    'password' => null, // Will generate random password
                    'provider' => 'email'
                ]);
                
                if ($result->success) {
                    $this->info("✓ Successfully provisioned: {$org->org_slug}");
                } else {
                    $this->error("✗ Failed to provision: {$org->org_slug}");
                    $this->error("  Error: {$result->errorMessage}");
                }
            } catch (\Exception $e) {
                $this->error("✗ Exception provisioning: {$org->org_slug}");
                $this->error("  Error: {$e->getMessage()}");
            }
        }
        
        return 0;
    }
}
