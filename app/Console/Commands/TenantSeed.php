<?php

namespace App\Console\Commands;

use App\Contracts\DatabaseConnectionRouter;
use App\Models\Control\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TenantSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:seed {org_slug : The organization slug}
                            {--class= : The seeder class to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed a specific tenant database';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseConnectionRouter $connectionRouter): int
    {
        $orgSlug = $this->argument('org_slug');
        $seederClass = $this->option('class');
        
        $this->info("Seeding tenant database: {$orgSlug}");
        
        // Find organization by slug
        $organization = Organization::where('org_slug', $orgSlug)->first();
        
        if (!$organization) {
            $this->error("Organization not found: {$orgSlug}");
            return Command::FAILURE;
        }
        
        if (!$organization->tenant_db_name) {
            $this->error("Organization does not have a tenant database configured.");
            return Command::FAILURE;
        }
        
        $this->info("Organization: {$organization->org_name}");
        $this->info("Database: {$organization->tenant_db_name}");
        $this->newLine();
        
        try {
            // Switch to tenant database
            $connectionRouter->switchToTenant($organization->tenant_db_name);
            
            if ($seederClass) {
                // Run specific seeder
                $this->info("Running seeder: {$seederClass}");
                
                Artisan::call('db:seed', [
                    '--database' => 'tenant',
                    '--class' => $seederClass,
                    '--force' => true
                ]);
            } else {
                // Run default tenant seeders
                $this->info('Running default tenant seeders...');
                
                $seeders = [
                    'Database\\Seeders\\Tenant\\DefaultRoleSeeder',
                    'Database\\Seeders\\Tenant\\DefaultRolePermissionSeeder',
                ];
                
                foreach ($seeders as $seeder) {
                    $this->line("  • {$seeder}");
                    
                    Artisan::call('db:seed', [
                        '--database' => 'tenant',
                        '--class' => $seeder,
                        '--force' => true
                    ]);
                }
            }
            
            $this->newLine();
            $this->info('✓ Seeding completed successfully!');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('✗ Seeding failed!');
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        } finally {
            // Switch back to control database
            $connectionRouter->switchToControl();
        }
    }
}
