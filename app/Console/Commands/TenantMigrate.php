<?php

namespace App\Console\Commands;

use App\Contracts\DatabaseConnectionRouter;
use App\Models\Control\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TenantMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate {org_slug : The organization slug}
                            {--fresh : Drop all tables and re-run all migrations}
                            {--seed : Seed the database after migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations on a specific tenant database';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseConnectionRouter $connectionRouter): int
    {
        $orgSlug = $this->argument('org_slug');
        $fresh = $this->option('fresh');
        $seed = $this->option('seed');
        
        $this->info("Running migrations for tenant: {$orgSlug}");
        
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
            
            // Run migrations
            if ($fresh) {
                $this->warn('Running fresh migrations (all tables will be dropped)...');
                
                if (!$this->confirm('Are you sure you want to drop all tables?')) {
                    $this->info('Migration cancelled.');
                    return Command::SUCCESS;
                }
                
                Artisan::call('migrate:fresh', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true
                ]);
            } else {
                $this->info('Running migrations...');
                
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true
                ]);
            }
            
            $this->info(Artisan::output());
            
            // Run seeders if requested
            if ($seed) {
                $this->info('Seeding database...');
                
                Artisan::call('db:seed', [
                    '--database' => 'tenant',
                    '--class' => 'Database\\Seeders\\Tenant\\DefaultRoleSeeder',
                    '--force' => true
                ]);
                
                Artisan::call('db:seed', [
                    '--database' => 'tenant',
                    '--class' => 'Database\\Seeders\\Tenant\\DefaultRolePermissionSeeder',
                    '--force' => true
                ]);
                
                $this->info('Database seeded successfully.');
            }
            
            $this->newLine();
            $this->info('✓ Migrations completed successfully!');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('✗ Migration failed!');
            $this->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        } finally {
            // Switch back to control database
            $connectionRouter->switchToControl();
        }
    }
}
