<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Control\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'plan_code' => 'TRIAL',
                'plan_name' => 'Trial Plan',
                'description' => '14-day free trial with full access',
                'billing_cycle' => 'MONTHLY',
                'price_amount' => 0.00,
                'currency_code' => 'INR',
                'max_users' => 5,
                'max_warehouses' => 1,
                'max_materials' => 100,
                'storage_gb' => 5,
                'api_rate_limit_day' => 1000,
                'modules_included' => json_encode(['PR', 'PO', 'GRN', 'QC', 'INVOICE', 'PAYMENT', 'INVENTORY', 'REPORTS', 'USERS', 'SETTINGS']),
                'is_active' => true,
                'is_public' => true,
            ],
            [
                'plan_code' => 'BASIC',
                'plan_name' => 'Basic Plan',
                'description' => 'Basic features for small teams',
                'billing_cycle' => 'MONTHLY',
                'price_amount' => 999.00,
                'currency_code' => 'INR',
                'max_users' => 10,
                'max_warehouses' => 2,
                'max_materials' => 500,
                'storage_gb' => 10,
                'api_rate_limit_day' => 5000,
                'modules_included' => json_encode(['PR', 'PO', 'GRN', 'QC', 'INVOICE', 'PAYMENT', 'INVENTORY', 'REPORTS', 'USERS', 'SETTINGS']),
                'is_active' => true,
                'is_public' => true,
            ],
            [
                'plan_code' => 'PROFESSIONAL',
                'plan_name' => 'Professional Plan',
                'description' => 'Advanced features for growing businesses',
                'billing_cycle' => 'MONTHLY',
                'price_amount' => 2999.00,
                'currency_code' => 'INR',
                'max_users' => 50,
                'max_warehouses' => 5,
                'max_materials' => 2000,
                'storage_gb' => 50,
                'api_rate_limit_day' => 20000,
                'modules_included' => json_encode(['PR', 'PO', 'GRN', 'QC', 'INVOICE', 'PAYMENT', 'INVENTORY', 'WAREHOUSE', 'REPORTS', 'SETTINGS']),
                'is_active' => true,
                'is_public' => true,
            ],
            [
                'plan_code' => 'ENTERPRISE',
                'plan_name' => 'Enterprise Plan',
                'description' => 'Full features for large organizations',
                'billing_cycle' => 'MONTHLY',
                'price_amount' => 9999.00,
                'currency_code' => 'INR',
                'max_users' => 999,
                'max_warehouses' => 999,
                'max_materials' => 999999,
                'storage_gb' => 500,
                'api_rate_limit_day' => 100000,
                'modules_included' => json_encode(['PR', 'PO', 'GRN', 'QC', 'INVOICE', 'PAYMENT', 'INVENTORY', 'WAREHOUSE', 'REPORTS', 'SETTINGS']),
                'is_active' => true,
                'is_public' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['plan_code' => $plan['plan_code']],
                $plan
            );
        }

        $this->command->info('Subscription plans seeded successfully!');
    }
}
