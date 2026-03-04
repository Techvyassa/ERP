<?php

namespace App\Console\Commands;

use App\Models\Control\SubscriptionPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSubscriptionPlansJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:fix-json';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix double-encoded JSON in subscription_plans modules_included field';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing subscription plans JSON encoding...');
        
        $plans = DB::connection('control')
            ->table('subscription_plans')
            ->get();
        
        $fixed = 0;
        
        foreach ($plans as $plan) {
            $modulesIncluded = $plan->modules_included;
            
            // Check if it's double-encoded
            if (is_string($modulesIncluded)) {
                // Try to decode once
                $decoded = json_decode($modulesIncluded, true);
                
                // If it's still a string, it's double-encoded
                if (is_string($decoded)) {
                    $finalDecoded = json_decode($decoded, true);
                    
                    if (is_array($finalDecoded)) {
                        // Re-encode properly (single encoding)
                        $properJson = json_encode($finalDecoded);
                        
                        DB::connection('control')
                            ->table('subscription_plans')
                            ->where('plan_id', $plan->plan_id)
                            ->update(['modules_included' => $properJson]);
                        
                        $this->info("Fixed plan: {$plan->plan_code} ({$plan->plan_name})");
                        $fixed++;
                    }
                } elseif (is_array($decoded)) {
                    $this->info("Plan {$plan->plan_code} is already properly encoded");
                }
            }
        }
        
        $this->info("Fixed {$fixed} subscription plan(s)");
        
        // Verify the fix
        $this->info("\nVerifying...");
        $plans = SubscriptionPlan::all();
        
        foreach ($plans as $plan) {
            $modules = $plan->modules_included;
            if (is_array($modules)) {
                $this->info("✓ {$plan->plan_code}: " . count($modules) . " modules");
            } else {
                $this->error("✗ {$plan->plan_code}: Still has issues");
            }
        }
        
        return 0;
    }
}
