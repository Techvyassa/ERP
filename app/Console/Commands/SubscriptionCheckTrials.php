<?php

namespace App\Console\Commands;

use App\Helpers\AuditLogger;
use App\Models\Control\OrgSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionCheckTrials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:check-trials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and expire trial subscriptions that have reached their end date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired trial subscriptions...');
        $this->newLine();
        
        try {
            // Find all TRIAL subscriptions where trial_end_date has passed
            $expiredTrials = OrgSubscription::where('subscription_status', 'TRIAL')
                ->whereNotNull('trial_end_date')
                ->where('trial_end_date', '<', Carbon::now()->toDateString())
                ->get();
            
            if ($expiredTrials->isEmpty()) {
                $this->info('No expired trial subscriptions found.');
                return Command::SUCCESS;
            }
            
            $this->info("Found {$expiredTrials->count()} expired trial subscription(s)");
            $this->newLine();
            
            $expiredCount = 0;
            
            foreach ($expiredTrials as $subscription) {
                $organization = $subscription->organization;
                
                $this->line("Processing: {$organization->org_name} ({$organization->org_slug})");
                $this->line("  Trial ended: {$subscription->trial_end_date}");
                
                try {
                    DB::connection('control')->transaction(function () use ($subscription, $organization) {
                        // Update subscription status to EXPIRED
                        $subscription->update([
                            'subscription_status' => 'EXPIRED',
                        ]);
                        
                        // Log the status change
                        AuditLogger::logSubscriptionChange(
                            $organization->org_id,
                            $subscription->subscription_id,
                            'TRIAL',
                            'EXPIRED',
                            'Trial period ended',
                            ['trial_end_date' => $subscription->trial_end_date]
                        );
                        
                        Log::info('Trial subscription expired', [
                            'org_id' => $organization->org_id,
                            'org_slug' => $organization->org_slug,
                            'subscription_id' => $subscription->subscription_id,
                            'trial_end_date' => $subscription->trial_end_date
                        ]);
                    });
                    
                    $this->line("  ✓ Expired");
                    $expiredCount++;
                    
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed: {$e->getMessage()}");
                    Log::error('Failed to expire trial subscription', [
                        'subscription_id' => $subscription->subscription_id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                $this->newLine();
            }
            
            // Summary
            $this->info('=== Summary ===');
            $this->info("Total expired trials: {$expiredTrials->count()}");
            $this->info("Successfully processed: {$expiredCount}");
            
            if ($expiredCount < $expiredTrials->count()) {
                $this->warn("Failed: " . ($expiredTrials->count() - $expiredCount));
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Command failed!');
            $this->error("Error: {$e->getMessage()}");
            Log::error('subscription:check-trials command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
