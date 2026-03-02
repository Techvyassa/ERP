<?php

namespace Database\Factories\Control;

use App\Models\Control\PaymentRecord;
use App\Models\Control\Organization;
use App\Models\Control\OrgSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Control\PaymentRecord>
 */
class PaymentRecordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $taxableAmount = fake()->randomFloat(2, 100, 10000);
        $cgstAmount = $taxableAmount * 0.09; // 9% CGST
        $sgstAmount = $taxableAmount * 0.09; // 9% SGST
        $totalAmount = $taxableAmount + $cgstAmount + $sgstAmount;
        
        return [
            'org_id' => Organization::factory(),
            'subscription_id' => OrgSubscription::factory(),
            'payment_reference' => 'PAY-' . strtoupper(Str::random(12)),
            'payment_type' => 'INVOICE',
            'payment_status' => 'SUCCESS',
            'taxable_amount' => $taxableAmount,
            'cgst_amount' => $cgstAmount,
            'sgst_amount' => $sgstAmount,
            'igst_amount' => 0.00,
            'total_amount' => $totalAmount,
            'gateway_name' => fake()->randomElement(['razorpay', 'stripe']),
            'gateway_payment_id' => 'gw_' . strtoupper(Str::random(16)),
            'gateway_response' => [
                'status' => 'success',
                'transaction_id' => Str::random(20),
            ],
            'payment_date' => now(),
            'created_at' => now(),
        ];
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'PENDING',
            'payment_date' => null,
            'gateway_payment_id' => null,
            'gateway_response' => null,
        ]);
    }

    /**
     * Indicate that the payment is successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'SUCCESS',
            'payment_date' => now(),
        ]);
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'FAILED',
            'payment_date' => null,
            'gateway_response' => [
                'status' => 'failed',
                'error' => fake()->sentence(),
            ],
        ]);
    }

    /**
     * Indicate that the payment is a refund.
     */
    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'REFUND',
            'payment_status' => 'SUCCESS',
            'taxable_amount' => -abs($attributes['taxable_amount']),
            'cgst_amount' => -abs($attributes['cgst_amount']),
            'sgst_amount' => -abs($attributes['sgst_amount']),
            'total_amount' => -abs($attributes['total_amount']),
        ]);
    }

    /**
     * Indicate that the payment uses IGST (interstate).
     */
    public function interstate(): static
    {
        return $this->state(function (array $attributes) {
            $taxableAmount = $attributes['taxable_amount'];
            $igstAmount = $taxableAmount * 0.18; // 18% IGST
            $totalAmount = $taxableAmount + $igstAmount;
            
            return [
                'cgst_amount' => 0.00,
                'sgst_amount' => 0.00,
                'igst_amount' => $igstAmount,
                'total_amount' => $totalAmount,
            ];
        });
    }

    /**
     * Indicate that the payment uses Razorpay gateway.
     */
    public function razorpay(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway_name' => 'razorpay',
            'gateway_payment_id' => 'pay_' . strtoupper(Str::random(14)),
        ]);
    }

    /**
     * Indicate that the payment uses Stripe gateway.
     */
    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway_name' => 'stripe',
            'gateway_payment_id' => 'pi_' . strtoupper(Str::random(24)),
        ]);
    }

    /**
     * Indicate that the payment is an advance payment.
     */
    public function advance(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'ADVANCE',
            'subscription_id' => null,
        ]);
    }

    /**
     * Indicate that the payment is a credit note.
     */
    public function creditNote(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'CREDIT_NOTE',
        ]);
    }
}
