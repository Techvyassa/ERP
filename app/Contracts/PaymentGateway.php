<?php

namespace App\Contracts;

interface PaymentGateway
{
    /**
     * Create payment intent
     * @param float $amount
     * @param string $currency
     * @param array $metadata
     * @return array Gateway response
     */
    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): array;
    
    /**
     * Verify webhook signature
     * @param array $payload
     * @param string $signature
     * @return bool
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;
    
    /**
     * Get payment status
     * @param string $paymentId
     * @return string
     */
    public function getPaymentStatus(string $paymentId): string;
    
    /**
     * Process refund
     * @param string $paymentId
     * @param float $amount
     * @return array Gateway response
     */
    public function processRefund(string $paymentId, float $amount): array;
}
