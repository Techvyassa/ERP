<?php

namespace Database\Factories\Control;

use App\Models\Control\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Control\SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $modules = ['PR', 'PO', 'GRN', 'QC', 'INVOICE', 'PAYMENT', 'INVENTORY', 'REPORTS'];
        
        return [
            'plan_code' => fake()->unique()->regexify('[A-Z]{4}_[0-9]{3}'),
            'plan_name' => fake()->words(2, true) . ' Plan',
            'description' => fake()->sentence(),
            'billing_cycle' => fake()->randomElement(['MONTHLY', 'QUARTERLY', 'ANNUAL']),
            'price_amount' => fake()->randomFloat(2, 99, 9999),
            'currency_code' => 'USD',
            'max_users' => fake()->randomElement([10, 25, 50, 100, 500]),
            'max_warehouses' => fake()->randomElement([1, 5, 10, 50]),
            'max_materials' => fake()->randomElement([100, 500, 1000, 10000]),
            'storage_gb' => fake()->randomElement([10, 50, 100, 500, 1000]),
            'api_rate_limit_day' => fake()->randomElement([1000, 5000, 10000, 50000, 100000]),
            'modules_included' => fake()->randomElements($modules, fake()->numberBetween(3, 8)),
            'is_active' => true,
            'is_public' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the plan is a basic plan.
     */
    public function basic(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_code' => 'BASIC',
            'plan_name' => 'Basic Plan',
            'billing_cycle' => 'MONTHLY',
            'price_amount' => 99.00,
            'max_users' => 10,
            'max_warehouses' => 1,
            'max_materials' => 100,
            'storage_gb' => 10,
            'api_rate_limit_day' => 1000,
            'modules_included' => ['PR', 'PO', 'GRN', 'INVENTORY'],
        ]);
    }

    /**
     * Indicate that the plan is a professional plan.
     */
    public function professional(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_code' => 'PROFESSIONAL',
            'plan_name' => 'Professional Plan',
            'billing_cycle' => 'MONTHLY',
            'price_amount' => 299.00,
            'max_users' => 50,
            'max_warehouses' => 5,
            'max_materials' => 1000,
            'storage_gb' => 100,
            'api_rate_limit_day' => 10000,
            'modules_included' => ['PR', 'PO', 'GRN', 'QC', 'INVOICE', 'PAYMENT', 'INVENTORY', 'REPORTS'],
        ]);
    }

    /**
     * Indicate that the plan is an enterprise plan.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_code' => 'ENTERPRISE',
            'plan_name' => 'Enterprise Plan',
            'billing_cycle' => 'ANNUAL',
            'price_amount' => 9999.00,
            'max_users' => 500,
            'max_warehouses' => 50,
            'max_materials' => 10000,
            'storage_gb' => 1000,
            'api_rate_limit_day' => 100000,
            'modules_included' => ['PR', 'PO', 'GRN', 'QC', 'INVOICE', 'PAYMENT', 'INVENTORY', 'REPORTS'],
        ]);
    }

    /**
     * Indicate that the plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the plan is not public.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }
}
