<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class StripeGateway implements PaymentGateway
{
    private string $secretKey;
    private string $webhookSecret;
    private string $baseUrl;
    
    public function __construct()
    {
        $this->secretKey = config('payment.stripe.secret_key', '');
        $this->webhookSecret = config('payment.stripe.webhook_secret', '');
        $this->baseUrl = config('payment.stripe.base_url', 'https://api.stripe.com/v1');
    }
    
    /**
     * Create payment intent
     */
    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): array
    {
        try {
            // Stripe expects amount in smallest currency unit (cents for USD)
            $amountInCents = (int) ($amount * 100);
            
            $response = Http::withToken($this->secretKey)
                ->asForm()
                ->post("{$this->baseUrl}/payment_intents", [
                    'amount' => $amountInCents,
                    'currency' => strtolower($currency),
                    'metadata' => $metadata,
                    'automatic_payment_methods' => [
                        'enabled' => true
                    ]
                ]);
            
            if ($response->failed()) {
                throw new Exception('Stripe API error: ' . $response->body());
            }
            
            $data = $response->json();
            
            Log::info('Stripe payment intent created', [
                'payment_intent_id' => $data['id'] ?? null,
                'amount' => $amount,
                'currency' => $currency
            ]);
            
            return $data;
            
        } catch (Exception $e) {
            Log::error('Stripe payment intent creation failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'currency' => $currency
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        try {
            // Stripe webhook signature verification
            // Expected format: t=timestamp,v1=signature,v0=signature
            $parts = explode(',', $signature);
            $timestamp = '';
            $signatures = [];
            
            foreach ($parts as $part) {
                $keyValue = explode('=', $part, 2);
                if (count($keyValue) === 2) {
                    if ($keyValue[0] === 't') {
                        $timestamp = $keyValue[1];
                    } elseif (in_array($keyValue[0], ['v1', 'v0'])) {
                        $signatures[] = $keyValue[1];
                    }
                }
            }
            
            // Construct signed payload
            $signedPayload = $timestamp . '.' . json_encode($payload);
            
            // Compute expected signature
            $expectedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);
            
            // Check if any signature matches
            $isValid = false;
            foreach ($signatures as $sig) {
                if (hash_equals($expectedSignature, $sig)) {
                    $isValid = true;
                    break;
                }
            }
            
            // Check timestamp tolerance (5 minutes)
            if ($isValid && $timestamp) {
                $timeDiff = time() - (int) $timestamp;
                if ($timeDiff > 300) {
                    Log::warning('Stripe webhook timestamp too old', [
                        'timestamp' => $timestamp,
                        'time_diff' => $timeDiff
                    ]);
                    $isValid = false;
                }
            }
            
            if (!$isValid) {
                Log::warning('Stripe webhook signature verification failed');
            }
            
            return $isValid;
            
        } catch (Exception $e) {
            Log::error('Stripe webhook signature verification error', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Get payment status
     */
    public function getPaymentStatus(string $paymentId): string
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->get("{$this->baseUrl}/payment_intents/{$paymentId}");
            
            if ($response->failed()) {
                throw new Exception('Stripe API error: ' . $response->body());
            }
            
            $data = $response->json();
            $status = $data['status'] ?? 'unknown';
            
            // Map Stripe status to our internal status
            return match($status) {
                'succeeded' => 'SUCCESS',
                'canceled', 'failed' => 'FAILED',
                'requires_payment_method', 'requires_confirmation', 'requires_action', 'processing' => 'PENDING',
                default => 'PENDING'
            };
            
        } catch (Exception $e) {
            Log::error('Stripe get payment status failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Process refund
     */
    public function processRefund(string $paymentId, float $amount): array
    {
        try {
            // Stripe expects amount in smallest currency unit (cents for USD)
            $amountInCents = (int) ($amount * 100);
            
            $response = Http::withToken($this->secretKey)
                ->asForm()
                ->post("{$this->baseUrl}/refunds", [
                    'payment_intent' => $paymentId,
                    'amount' => $amountInCents
                ]);
            
            if ($response->failed()) {
                throw new Exception('Stripe API error: ' . $response->body());
            }
            
            $data = $response->json();
            
            Log::info('Stripe refund processed', [
                'refund_id' => $data['id'] ?? null,
                'payment_intent_id' => $paymentId,
                'amount' => $amount
            ]);
            
            return $data;
            
        } catch (Exception $e) {
            Log::error('Stripe refund processing failed', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
