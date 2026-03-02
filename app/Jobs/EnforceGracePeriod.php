<?php

namespace App\Jobs;

use App\Models\Control\Organization;
use App\Models\Control\OrgSubscription;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnforceGracePeriod implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     * Requirements: 6.10
     */
    public function handle(): void
    {
        Log::info("Starting grace period enforcement");
        
        $now = Carbon::now();
        
        // Find all PAST_DUE subscriptions where grace_period_until has been exceeded
        $expiredGracePeriods = OrgSubscription::where('subscription_status', 'PAST_DUE')
            ->whereNotNull('grace_period_until')
            ->where('grace_period_until', '<=', $now)
            ->get();
        
        $suspendedCount = 0;
        
        DB::connection('control')->transaction(function () use ($expiredGracePeriods, $now, &$suspendedCount) {
            foreach ($expiredGracePeriods as $subscription) {
                // Suspend the organization
                $organization = Organization::find($subscription->org_id);
                
                if ($organization && $organization->registration_status === 'ACTIVE') {
                    $organization->update([
                        'registration_status' => 'SUSPENDED',
                        'suspended_at' => $now,
                    ]);
                    
                    Log::info("Organization suspended due to grace period expiration", [
                        'org_id' => $organization->org_id,
                        'subscription_id' => $subscription->subscription_id,
                        'grace_period_until' => $subscription->grace_period_until,
                    ]);
                    
                    $suspendedCount++;
                }
            }
        });
        
        Log::info("Grace period enforcement completed", [
            'suspended_count' => $suspendedCount,
        ]);
    }
}
