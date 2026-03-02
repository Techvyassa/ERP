<?php

namespace App\Console\Commands;

use App\Contracts\DatabaseConnectionRouter;
use App\Models\Control\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TenantMigrateAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate-all {--fresh : Drop all tables and re-run all migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations on all tenant databases';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseConnectionRouter $connectionRouter): int
    {
        $fresh = $this->option('fresh');
        
        $this->info('Running migrations on all tenant databases...');
        $this->newLine();
        
        // Get all active organizations with tenant databases
        $organizations = Organization::whereNotNull('tenant_db_name')
            ->where('registration_status', 'ACTIVE')
            ->get();
        
        if ($organizations->isEmpty()) {
            $this->warn('No active tenant databases found.');
            return Command::SUCCESS;
        }
        
        $this->info("Found {$organizations->count()} tenant database(s)");
        $this->newLine();
        
        if ($fresh && !$this->confirm('Are you sure you want to run fresh migrations on ALL tenants? This will drop all tables!')) {
            $this->info('Migration cancelled.');
            return Command::SUCCESS;
        }
        
        $successCount = 0;
        $failureCount = 0;
        $failures = [];
        
        foreach ($organizations as $organization) {
            $this->info("Processing: {$organization->org_name} ({$organization->org_slug})");
            $this->line("  Database: {$organization->tenant_db_name}");
            
            try {
                // Switch to tenant database
                $connectionRouter->switchToTenant($organization->tenant_db_name);
                
                // Run migrations
                if ($fresh) {
                    Artisan::call('migrate:fresh', [
                        '--database' => 'tenant',
                        '--path' => 'database/migrations/tenant',
                        '--force' => true
                    ]);
                } else {
                    Artisan::call('migrate', [
                        '--database' => 'tenant',
                        '--path' => 'database/migrations/tenant',
                        '--force' => true
                    ]);
                }
                
                $this->line("  ✓ Success");
                $successCount++;
                
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
                $failureCount++;
                $failures[] = [
                    'org' => $organization->org_slug,
                    'error' => $e->getMessage()
                ];
            } finally {
                // Switch back to control database
                $connectionRouter->switchToControl();
            }
            
            $this->newLine();
        }
        
        // Summary
        $this->info('=== Migration Summary ===');
        $this->info("Total tenants: {$organizations->count()}");
        $this->info("Successful: {$successCount}");
        
        if ($failureCount > 0) {
            $this->error("Failed: {$failureCount}");
            $this->newLine();
            $this->error('Failed tenants:');
            foreach ($failures as $failure) {
                $this->line("  • {$failure['org']}: {$failure['error']}");
            }
            return Command::FAILURE;
        }
        
        $this->newLine();
        $this->info('✓ All migrations completed successfully!');
        
        return Command::SUCCESS;
    }
}
