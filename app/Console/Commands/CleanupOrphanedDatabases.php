<?php

namespace App\Console\Commands;

use App\Models\Control\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupOrphanedDatabases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:cleanup-orphaned 
                            {--days=7 : Number of days before considering a database orphaned}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup orphaned tenant databases that are not linked to any organization';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("Scanning for orphaned tenant databases (older than {$days} days)...");
        $this->newLine();
        
        try {
            // Get all databases with erp_ prefix
            $prefix = config('tenant.database_prefix', 'erp_');
            $databases = $this->getTenantDatabases($prefix);
            
            $this->info("Found " . count($databases) . " tenant databases");
            
            // Get all tenant_db_names from organizations table
            $linkedDatabases = Organization::whereNotNull('tenant_db_name')
                ->pluck('tenant_db_name')
                ->toArray();
            
            $this->info("Found " . count($linkedDatabases) . " linked databases in organizations table");
            $this->newLine();
            
            // Find orphaned databases
            $orphanedDatabases = array_diff($databases, $linkedDatabases);
            
            if (empty($orphanedDatabases)) {
                $this->info('✓ No orphaned databases found!');
                return Command::SUCCESS;
            }
            
            $this->warn("Found " . count($orphanedDatabases) . " orphaned database(s):");
            $this->newLine();
            
            foreach ($orphanedDatabases as $dbName) {
                $createdAt = $this->getDatabaseCreationTime($dbName);
                $age = $createdAt ? now()->diffInDays($createdAt) : 'unknown';
                
                $this->line("  • {$dbName} (age: {$age} days)");
                
                // Only delete if older than specified days
                if (is_numeric($age) && $age >= $days) {
                    if ($dryRun) {
                        $this->comment("    [DRY RUN] Would delete: {$dbName}");
                    } else {
                        if ($this->confirm("Delete database '{$dbName}'?", false)) {
                            $this->deleteTenantDatabase($dbName);
                            $this->info("    ✓ Deleted: {$dbName}");
                        } else {
                            $this->comment("    Skipped: {$dbName}");
                        }
                    }
                } else {
                    $this->comment("    Skipped (too recent): {$dbName}");
                }
            }
            
            $this->newLine();
            
            if ($dryRun) {
                $this->info('Dry run completed. No databases were deleted.');
            } else {
                $this->info('Cleanup completed.');
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Cleanup failed: {$e->getMessage()}");
            Log::error('Orphaned database cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
    
    /**
     * Get all tenant databases with the specified prefix
     */
    private function getTenantDatabases(string $prefix): array
    {
        $results = DB::connection('control')->select(
            "SELECT SCHEMA_NAME 
             FROM INFORMATION_SCHEMA.SCHEMATA 
             WHERE SCHEMA_NAME LIKE ?",
            ["{$prefix}%"]
        );
        
        return array_map(fn($row) => $row->SCHEMA_NAME, $results);
    }
    
    /**
     * Get database creation time (approximate)
     */
    private function getDatabaseCreationTime(string $dbName): ?\Carbon\Carbon
    {
        try {
            $result = DB::connection('control')->select(
                "SELECT CREATE_TIME 
                 FROM INFORMATION_SCHEMA.TABLES 
                 WHERE TABLE_SCHEMA = ? 
                 ORDER BY CREATE_TIME ASC 
                 LIMIT 1",
                [$dbName]
            );
            
            if (!empty($result) && isset($result[0]->CREATE_TIME)) {
                return \Carbon\Carbon::parse($result[0]->CREATE_TIME);
            }
        } catch (\Exception $e) {
            Log::warning("Could not determine creation time for database: {$dbName}");
        }
        
        return null;
    }
    
    /**
     * Delete a tenant database
     */
    private function deleteTenantDatabase(string $dbName): void
    {
        DB::connection('control')->statement("DROP DATABASE IF EXISTS `{$dbName}`");
        
        Log::info("Deleted orphaned tenant database", [
            'database' => $dbName,
            'deleted_at' => now()->toIso8601String()
        ]);
    }
}
