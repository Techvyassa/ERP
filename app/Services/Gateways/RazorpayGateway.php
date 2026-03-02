<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class RazorpayGateway implements PaymentGateway
{
    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;
    private string $baseUrl;
    
    public function __construct()
    {
        $this->keyId = config('payment.razorpay.key_id', '');
        $this->keySecret = config('payment.razorpay.key_secret', '');
        $this->webhookSecret = config('payment.razorpay.webhook_secret', '');
        $this->baseUrl = config('payment.razorpay.base_url', 'https://api.razorpay.com/v1');
    }
    
    /**
     * Create payment intent (order in Razorpay)
     */
    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): array
    {
        try {
            // Razorpay expects amount in smallest currency unit (paise for INR)
            $amountInPaise = (int) ($amount * 100);
            
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post("{$this->baseUrl}/orders", [
                    'amount' => $amountInPaise,
                    'currency' => $currency,
                    'receipt' => $metadata['payment_reference'] ?? 'rcpt_' . time(),
                    'notes' => $metadata
                ]);
            
            if ($response->failed()) {
                throw new Exception('Razorpay API error: ' . $response->body());
            }
            
            $data = $response->json();
            
            Log::info('Razorpay payment intent created', [
                'order_id' => $data['id'] ?? null,
                'amount' => $amount,
                'currency' => $currency
            ]);
            
            return $data;
            
        } catch (Exception $e) {
            Log::error('Razorpay payment intent creation failed', [
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
            // Razorpay webhook signature verification
            // Expected format: t=timestamp,v1=signature
            $expectedSignature = hash_hmac(
                'sha256',
                json_encode($payload),
                $this->webhookSecret
            );
            
            // Extract signature from header
            $parts = explode(',', $signature);
            $receivedSignature = '';
            
            foreach ($parts as $part) {
                if (strpos($part, 'v1=') === 0) {
                    $receivedSignature = substr($part, 3);
                    break;
                }
            }
            
            $isValid = hash_equals($expectedSignature, $receivedSignature);
            
            if (!$isValid) {
                Log::warning('Razorpay webhook signature verification failed', [
                    'expected' => $expectedSignature,
                    'received' => $receivedSignature
                ]);
            }
            
            return $isValid;
            
        } catch (Exception $e) {
            Log::error('Razorpay webhook signature verification error', [
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
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->get("{$this->baseUrl}/payments/{$paymentId}");
            
            if ($response->failed()) {
                throw new Exception('Razorpay API error: ' . $response->body());
            }
            
            $data = $response->json();
            $status = $data['status'] ?? 'unknown';
            
            // Map Razorpay status to our internal status
            return match($status) {
                'captured', 'authorized' => 'SUCCESS',
                'failed' => 'FAILED',
                'created' => 'PENDING',
                default => 'PENDING'
            };
            
        } catch (Exception $e) {
            Log::error('Razorpay get payment status failed', [
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
            // Razorpay expects amount in smallest currency unit (paise for INR)
            $amountInPaise = (int) ($amount * 100);
            
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->post("{$this->baseUrl}/payments/{$paymentId}/refund", [
                    'amount' => $amountInPaise
                ]);
            
            if ($response->failed()) {
                throw new Exception('Razorpay API error: ' . $response->body());
            }
            
            $data = $response->json();
            
            Log::info('Razorpay refund processed', [
                'refund_id' => $data['id'] ?? null,
                'payment_id' => $paymentId,
                'amount' => $amount
            ]);
            
            return $data;
            
        } catch (Exception $e) {
            Log::error('Razorpay refund processing failed', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
