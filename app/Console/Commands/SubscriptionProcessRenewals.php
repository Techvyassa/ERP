<?php

namespace App\Console\Commands;

use App\Contracts\SubscriptionManagementService;
use App\Models\Control\OrgSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SubscriptionProcessRenewals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:process-renewals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process subscription renewals for subscriptions due for billing';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionManagementService $subscriptionService): int
    {
        $this->info('Processing subscription renewals...');
        $this->newLine();
        
        try {
            // Find all ACTIVE subscriptions where next_billing_date has passed
            $dueSubscriptions = OrgSubscription::where('subscription_status', 'ACTIVE')
                ->whereNotNull('next_billing_date')
                ->where('next_billing_date', '<=', Carbon::now()->toDateString())
                ->get();
            
            if ($dueSubscriptions->isEmpty()) {
                $this->info('No subscriptions due for renewal.');
                return Command::SUCCESS;
            }
            
            $this->info("Found {$dueSubscriptions->count()} subscription(s) due for renewal");
            $this->newLine();
            
            $successCount = 0;
            $failureCount = 0;
            $failures = [];
            
            foreach ($dueSubscriptions as $subscription) {
                $organization = $subscription->organization;
                
                $this->line("Processing: {$organization->org_name} ({$organization->org_slug})");
                $this->line("  Subscription ID: {$subscription->subscription_id}");
                $this->line("  Next billing date: {$subscription->next_billing_date}");
                
                try {
                    // Process renewal
                    $result = $subscriptionService->processRenewal($subscription->subscription_id);
                    
                    if ($result->success) {
                        $this->line("  ✓ Renewal successful");
                        $this->line("  New period end: {$result->subscription->current_period_end}");
                        $successCount++;
                    } else {
                        $this->warn("  ⚠ Renewal failed: {$result->errorMessage}");
                        $this->line("  Payment status: {$result->paymentStatus}");
                        
                        if ($result->paymentStatus === 'FAILED') {
                            $this->line("  Status changed to: PAST_DUE");
                            $this->line("  Grace period until: {$result->subscription->grace_period_until}");
                        }
                        
                        $failureCount++;
                        $failures[] = [
                            'org' => $organization->org_slug,
                            'error' => $result->errorMessage,
                            'payment_status' => $result->paymentStatus
                        ];
                    }
                    
                } catch (\Exception $e) {
                    $this->error("  ✗ Error: {$e->getMessage()}");
                    $failureCount++;
                    $failures[] = [
                        'org' => $organization->org_slug,
                        'error' => $e->getMessage(),
                        'payment_status' => 'ERROR'
                    ];
                    
                    Log::error('Failed to process subscription renewal', [
                        'subscription_id' => $subscription->subscription_id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                $this->newLine();
            }
            
            // Summary
            $this->info('=== Renewal Summary ===');
            $this->info("Total subscriptions processed: {$dueSubscriptions->count()}");
            $this->info("Successful renewals: {$successCount}");
            
            if ($failureCount > 0) {
                $this->warn("Failed renewals: {$failureCount}");
                $this->newLine();
                $this->warn('Failed subscriptions:');
                foreach ($failures as $failure) {
                    $this->line("  • {$failure['org']}: {$failure['error']} (Payment: {$failure['payment_status']})");
                }
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Command failed!');
            $this->error("Error: {$e->getMessage()}");
            Log::error('subscription:process-renewals command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
