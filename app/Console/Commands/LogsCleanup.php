<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class LogsCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:cleanup {--days=90 : Number of days to retain logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove old log files older than specified days (default: 90 days)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retentionDays = (int) $this->option('days');
        
        if ($retentionDays < 1) {
            $this->error('Retention days must be at least 1.');
            return Command::FAILURE;
        }
        
        $this->info("Cleaning up log files older than {$retentionDays} days...");
        $this->newLine();
        
        try {
            $logPath = storage_path('logs');
            
            if (!File::exists($logPath)) {
                $this->warn('Log directory does not exist.');
                return Command::SUCCESS;
            }
            
            $cutoffDate = Carbon::now()->subDays($retentionDays);
            $this->info("Cutoff date: {$cutoffDate->toDateString()}");
            $this->newLine();
            
            $files = File::files($logPath);
            $deletedCount = 0;
            $deletedSize = 0;
            $skippedCount = 0;
            
            foreach ($files as $file) {
                $fileName = $file->getFilename();
                
                // Skip current log file (laravel.log)
                if ($fileName === 'laravel.log') {
                    $this->line("Skipping current log file: {$fileName}");
                    $skippedCount++;
                    continue;
                }
                
                // Get file modification time
                $fileModifiedTime = Carbon::createFromTimestamp($file->getMTime());
                
                // Check if file is older than cutoff date
                if ($fileModifiedTime->lt($cutoffDate)) {
                    $fileSize = $file->getSize();
                    $fileSizeKB = round($fileSize / 1024, 2);
                    
                    $this->line("Deleting: {$fileName}");
                    $this->line("  Modified: {$fileModifiedTime->toDateTimeString()}");
                    $this->line("  Size: {$fileSizeKB} KB");
                    
                    try {
                        File::delete($file->getPathname());
                        $deletedCount++;
                        $deletedSize += $fileSize;
                        $this->line("  ✓ Deleted");
                    } catch (\Exception $e) {
                        $this->error("  ✗ Failed: {$e->getMessage()}");
                    }
                    
                    $this->newLine();
                } else {
                    $skippedCount++;
                }
            }
            
            // Summary
            $deletedSizeMB = round($deletedSize / (1024 * 1024), 2);
            
            $this->info('=== Cleanup Summary ===');
            $this->info("Total log files: " . count($files));
            $this->info("Files deleted: {$deletedCount}");
            $this->info("Files retained: {$skippedCount}");
            $this->info("Space freed: {$deletedSizeMB} MB");
            
            Log::info('Log cleanup completed', [
                'retention_days' => $retentionDays,
                'cutoff_date' => $cutoffDate->toDateString(),
                'files_deleted' => $deletedCount,
                'files_retained' => $skippedCount,
                'space_freed_mb' => $deletedSizeMB
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Log cleanup failed!');
            $this->error("Error: {$e->getMessage()}");
            Log::error('logs:cleanup command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
