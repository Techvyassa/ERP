<?php

namespace Database\Seeders\Control;

use Illuminate\Database\Seeder;
use App\Models\Control\SubscriptionPlan;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates default subscription plans: BASIC, PROFESSIONAL, ENTERPRISE
     * 
     * Requirements: 2.10-2.13
     */
    public function run(): void
    {
        // Use Control database connection
        DB::connection('control')->transaction(function () {
            $plans = [
                [
                    'plan_code' => 'BASIC',
                    'plan_name' => 'Basic Plan',
                    'description' => 'Essential features for small teams',
                    'billing_cycle' => 'MONTHLY',
                    'price_amount' => 49.99,
                    'currency_code' => 'USD',
                    'max_users' => 10,
                    'max_warehouses' => 2,
                    'max_materials' => 500,
                    'storage_gb' => 10,
                    'api_rate_limit_day' => 1000,
                    'modules_included' => [
                        'INVENTORY',
                        'WAREHOUSE',
                        'MATERIAL',
                        'USER_MGMT',
                        'ROLE_MGMT',
                    ],
                    'is_active' => true,
                    'is_public' => true,
                ],
                [
                    'plan_code' => 'PROFESSIONAL',
                    'plan_name' => 'Professional Plan',
                    'description' => 'Advanced features for growing businesses',
                    'billing_cycle' => 'MONTHLY',
                    'price_amount' => 149.99,
                    'currency_code' => 'USD',
                    'max_users' => 50,
                    'max_warehouses' => 10,
                    'max_materials' => 5000,
                    'storage_gb' => 100,
                    'api_rate_limit_day' => 10000,
                    'modules_included' => [
                        'PR',
                        'PO',
                        'GRN',
                        'QC',
                        'INVOICE',
                        'PAYMENT',
                        'INVENTORY',
                        'WAREHOUSE',
                        'MATERIAL',
                        'USER_MGMT',
                        'ROLE_MGMT',
                        'DEPT_MGMT',
                    ],
                    'is_active' => true,
                    'is_public' => true,
                ],
                [
                    'plan_code' => 'ENTERPRISE',
                    'plan_name' => 'Enterprise Plan',
                    'description' => 'Complete solution for large organizations',
                    'billing_cycle' => 'ANNUAL',
                    'price_amount' => 1999.99,
                    'currency_code' => 'USD',
                    'max_users' => 500,
                    'max_warehouses' => 100,
                    'max_materials' => 100000,
                    'storage_gb' => 1000,
                    'api_rate_limit_day' => 100000,
                    'modules_included' => [
                        'PR',
                        'PO',
                        'GRN',
                        'QC',
                        'INVOICE',
                        'PAYMENT',
                        'INVENTORY',
                        'WAREHOUSE',
                        'MATERIAL',
                        'USER_MGMT',
                        'ROLE_MGMT',
                        'DEPT_MGMT',
                    ],
                    'is_active' => true,
                    'is_public' => true,
                ],
            ];

            foreach ($plans as $planData) {
                // Use updateOrCreate for idempotency
                SubscriptionPlan::updateOrCreate(
                    ['plan_code' => $planData['plan_code']],
                    $planData
                );
            }

            echo "✓ Subscription plans seeded successfully\n";
        });
    }
}
