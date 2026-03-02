<?php

namespace App\Jobs;

use App\Contracts\SubscriptionManagementService;
use App\Models\Control\OrgSubscription;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionRenewal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     * Requirements: 6.6
     */
    public function handle(SubscriptionManagementService $subscriptionService): void
    {
        Log::info("Starting subscription renewal processing");
        
        $today = Carbon::today();
        
        // Find all ACTIVE subscriptions where next_billing_date has been reached
        $dueRenewals = OrgSubscription::where('subscription_status', 'ACTIVE')
            ->whereNotNull('next_billing_date')
            ->whereDate('next_billing_date', '<=', $today)
            ->get();
        
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($dueRenewals as $subscription) {
            try {
                $result = $subscriptionService->processRenewal($subscription->subscription_id);
                
                if ($result->success) {
                    $successCount++;
                    Log::info("Subscription renewal processed successfully", [
                        'subscription_id' => $subscription->subscription_id,
                        'org_id' => $subscription->org_id,
                    ]);
                } else {
                    $failureCount++;
                    Log::warning("Subscription renewal failed", [
                        'subscription_id' => $subscription->subscription_id,
                        'org_id' => $subscription->org_id,
                        'error' => $result->errorMessage,
                    ]);
                }
            } catch (\Exception $e) {
                $failureCount++;
                Log::error("Exception during subscription renewal", [
                    'subscription_id' => $subscription->subscription_id,
                    'org_id' => $subscription->org_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        Log::info("Subscription renewal processing completed", [
            'total_processed' => $dueRenewals->count(),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
        ]);
    }
}
