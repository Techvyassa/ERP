<?php

namespace Database\Factories\Control;

use App\Models\Control\OrgSubscription;
use App\Models\Control\Organization;
use App\Models\Control\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Control\OrgSubscription>
 */
class OrgSubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OrgSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = now()->subDays(fake()->numberBetween(1, 30));
        $endDate = $startDate->copy()->addDays(30);
        
        return [
            'org_id' => Organization::factory(),
            'plan_id' => SubscriptionPlan::factory(),
            'subscription_status' => 'ACTIVE',
            'trial_start_date' => null,
            'trial_end_date' => null,
            'current_period_start' => $startDate,
            'current_period_end' => $endDate,
            'next_billing_date' => $endDate->copy()->addDay(),
            'grace_period_until' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the subscription is in trial.
     */
    public function trial(): static
    {
        return $this->state(function (array $attributes) {
            $trialStart = now()->subDays(fake()->numberBetween(1, 7));
            $trialEnd = $trialStart->copy()->addDays(14);
            
            return [
                'subscription_status' => 'TRIAL',
                'trial_start_date' => $trialStart,
                'trial_end_date' => $trialEnd,
                'current_period_start' => $trialStart,
                'current_period_end' => $trialEnd,
                'next_billing_date' => null,
            ];
        });
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_status' => 'ACTIVE',
        ]);
    }

    /**
     * Indicate that the subscription is past due.
     */
    public function pastDue(): static
    {
        return $this->state(function (array $attributes) {
            $gracePeriod = now()->addDays(7);
            
            return [
                'subscription_status' => 'PAST_DUE',
                'grace_period_until' => $gracePeriod,
            ];
        });
    }

    /**
     * Indicate that the subscription is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'subscription_status' => 'CANCELLED',
                'cancelled_at' => now()->subDays(fake()->numberBetween(1, 30)),
                'cancellation_reason' => fake()->sentence(),
            ];
        });
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $endDate = now()->subDays(fake()->numberBetween(1, 30));
            
            return [
                'subscription_status' => 'EXPIRED',
                'current_period_end' => $endDate,
                'next_billing_date' => null,
            ];
        });
    }

    /**
     * Indicate that the subscription is monthly.
     */
    public function monthly(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = now()->subDays(fake()->numberBetween(1, 30));
            $endDate = $startDate->copy()->addDays(30);
            
            return [
                'current_period_start' => $startDate,
                'current_period_end' => $endDate,
                'next_billing_date' => $endDate->copy()->addDay(),
            ];
        });
    }

    /**
     * Indicate that the subscription is quarterly.
     */
    public function quarterly(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = now()->subDays(fake()->numberBetween(1, 90));
            $endDate = $startDate->copy()->addDays(90);
            
            return [
                'current_period_start' => $startDate,
                'current_period_end' => $endDate,
                'next_billing_date' => $endDate->copy()->addDay(),
            ];
        });
    }

    /**
     * Indicate that the subscription is annual.
     */
    public function annual(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = now()->subDays(fake()->numberBetween(1, 365));
            $endDate = $startDate->copy()->addDays(365);
            
            return [
                'current_period_start' => $startDate,
                'current_period_end' => $endDate,
                'next_billing_date' => $endDate->copy()->addDay(),
            ];
        });
    }
}
