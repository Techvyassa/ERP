<?php

namespace App\Console\Commands;

use App\Helpers\AuditLogger;
use App\Models\Control\Organization;
use App\Models\Control\OrgSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionEnforceGrace extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:enforce-grace';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enforce grace period by suspending organizations with expired grace periods';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired grace periods...');
        $this->newLine();
        
        try {
            // Find all PAST_DUE subscriptions where grace_period_until has passed
            $expiredGracePeriods = OrgSubscription::where('subscription_status', 'PAST_DUE')
                ->whereNotNull('grace_period_until')
                ->where('grace_period_until', '<', Carbon::now())
                ->get();
            
            if ($expiredGracePeriods->isEmpty()) {
                $this->info('No expired grace periods found.');
                return Command::SUCCESS;
            }
            
            $this->info("Found {$expiredGracePeriods->count()} subscription(s) with expired grace period");
            $this->newLine();
            
            $suspendedCount = 0;
            
            foreach ($expiredGracePeriods as $subscription) {
                $organization = $subscription->organization;
                
                $this->line("Processing: {$organization->org_name} ({$organization->org_slug})");
                $this->line("  Grace period expired: {$subscription->grace_period_until}");
                $this->line("  Current org status: {$organization->registration_status}");
                
                try {
                    DB::connection('control')->transaction(function () use ($subscription, $organization) {
                        // Update subscription status to EXPIRED
                        $subscription->update([
                            'subscription_status' => 'EXPIRED',
                        ]);
                        
                        // Suspend the organization
                        $organization->update([
                            'registration_status' => 'SUSPENDED',
                            'suspended_at' => Carbon::now(),
                        ]);
                        
                        // Log subscription status change
                        AuditLogger::logSubscriptionChange(
                            $organization->org_id,
                            $subscription->subscription_id,
                            'PAST_DUE',
                            'EXPIRED',
                            'Grace period expired - organization suspended',
                            [
                                'grace_period_until' => $subscription->grace_period_until,
                                'suspended_at' => Carbon::now()->toDateTimeString()
                            ]
                        );
                        
                        Log::warning('Organization suspended due to expired grace period', [
                            'org_id' => $organization->org_id,
                            'org_slug' => $organization->org_slug,
                            'subscription_id' => $subscription->subscription_id,
                            'grace_period_until' => $subscription->grace_period_until
                        ]);
                    });
                    
                    $this->line("  ✓ Organization suspended");
                    $suspendedCount++;
                    
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed: {$e->getMessage()}");
                    Log::error('Failed to suspend organization', [
                        'org_id' => $organization->org_id,
                        'subscription_id' => $subscription->subscription_id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                $this->newLine();
            }
            
            // Summary
            $this->info('=== Summary ===');
            $this->info("Total expired grace periods: {$expiredGracePeriods->count()}");
            $this->info("Organizations suspended: {$suspendedCount}");
            
            if ($suspendedCount < $expiredGracePeriods->count()) {
                $this->warn("Failed: " . ($expiredGracePeriods->count() - $suspendedCount));
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Command failed!');
            $this->error("Error: {$e->getMessage()}");
            Log::error('subscription:enforce-grace command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
