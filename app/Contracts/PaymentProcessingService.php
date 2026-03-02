<?php

namespace App\Contracts;

interface PaymentProcessingService
{
    /**
     * Create payment intent for subscription
     * @param int $subscriptionId
     * @return PaymentIntent
     */
    public function createPaymentIntent(int $subscriptionId): PaymentIntent;
    
    /**
     * Process payment gateway callback
     * @param string $gatewayName (razorpay|stripe)
     * @param array $payload
     * @return PaymentResult
     */
    public function processCallback(string $gatewayName, array $payload): PaymentResult;
    
    /**
     * Record payment transaction
     * @param PaymentData $data
     * @return \App\Models\Control\PaymentRecord
     */
    public function recordPayment(PaymentData $data): \App\Models\Control\PaymentRecord;
    
    /**
     * Process refund
     * @param int $originalPaymentId
     * @param float $amount
     * @param string $reason
     * @return \App\Models\Control\PaymentRecord
     */
    public function processRefund(int $originalPaymentId, float $amount, string $reason): \App\Models\Control\PaymentRecord;
    
    /**
     * Calculate tax amounts based on country
     * @param float $taxableAmount
     * @param string $countryCode
     * @return TaxBreakdown
     */
    public function calculateTax(float $taxableAmount, string $countryCode): TaxBreakdown;
}
