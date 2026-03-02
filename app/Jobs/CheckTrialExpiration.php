<?php

namespace App\Jobs;

use App\Models\Control\OrgSubscription;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckTrialExpiration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     * Requirements: 6.3
     */
    public function handle(): void
    {
        Log::info("Starting trial expiration check");
        
        $today = Carbon::today();
        
        // Find all TRIAL subscriptions where trial_end_date has been reached
        $expiredTrials = OrgSubscription::where('subscription_status', 'TRIAL')
            ->whereDate('trial_end_date', '<=', $today)
            ->get();
        
        $expiredCount = 0;
        
        DB::connection('control')->transaction(function () use ($expiredTrials, &$expiredCount) {
            foreach ($expiredTrials as $subscription) {
                // Change subscription status to EXPIRED
                $subscription->update([
                    'subscription_status' => 'EXPIRED',
                ]);
                
                Log::info("Trial subscription expired", [
                    'subscription_id' => $subscription->subscription_id,
                    'org_id' => $subscription->org_id,
                    'trial_end_date' => $subscription->trial_end_date,
                ]);
                
                $expiredCount++;
            }
        });
        
        Log::info("Trial expiration check completed", [
            'expired_count' => $expiredCount,
        ]);
    }
}
