<?php

namespace App\Services;

use App\Contracts\PaymentProcessingService;
use App\Contracts\PaymentIntent;
use App\Contracts\PaymentResult;
use App\Contracts\PaymentData;
use App\Contracts\TaxBreakdown;
use App\Helpers\AuditLogger;
use App\Models\Control\PaymentRecord;
use App\Models\Control\OrgSubscription;
use App\Models\Control\Organization;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentProcessingServiceImpl implements PaymentProcessingService
{
    /**
     * Create payment intent for subscription
     */
    public function createPaymentIntent(int $subscriptionId): PaymentIntent
    {
        $subscription = OrgSubscription::with(['organization', 'plan'])
            ->findOrFail($subscriptionId);
        
        $organization = $subscription->organization;
        $plan = $subscription->plan;
        
        // Calculate tax based on country
        $taxBreakdown = $this->calculateTax(
            (float) $plan->price_amount,
            $organization->country_code
        );
        
        $totalAmount = (float) $plan->price_amount + $taxBreakdown->totalTax;
        
        // Generate unique payment reference
        $paymentReference = $this->generatePaymentReference();
        
        // Create payment record with PENDING status
        $paymentData = new PaymentData(
            orgId: $organization->org_id,
            subscriptionId: $subscriptionId,
            paymentType: 'INVOICE',
            paymentStatus: 'PENDING',
            taxableAmount: (float) $plan->price_amount,
            cgstAmount: $taxBreakdown->cgstAmount,
            sgstAmount: $taxBreakdown->sgstAmount,
            igstAmount: $taxBreakdown->igstAmount,
            totalAmount: $totalAmount
        );
        
        $paymentRecord = $this->recordPayment($paymentData);
        
        // Update payment record with reference
        PaymentRecord::where('payment_id', $paymentRecord->payment_id)
            ->update(['payment_reference' => $paymentReference]);
        
        Log::info('Payment intent created', [
            'subscription_id' => $subscriptionId,
            'payment_id' => $paymentRecord->payment_id,
            'payment_reference' => $paymentReference,
            'amount' => $totalAmount
        ]);
        
        // Return payment intent (gateway integration would happen here)
        return new PaymentIntent(
            subscriptionId: $subscriptionId,
            gatewayName: 'razorpay', // Default gateway
            gatewayPaymentId: $paymentReference, // Would be replaced by actual gateway ID
            amount: $totalAmount,
            currency: $plan->currency_code,
            gatewayResponse: []
        );
    }
    
    /**
     * Process payment gateway callback
     */
    public function processCallback(string $gatewayName, array $payload): PaymentResult
    {
        try {
            // Verify webhook signature (gateway-specific)
            if (!$this->verifyWebhookSignature($gatewayName, $payload)) {
                Log::warning('Invalid webhook signature', [
                    'gateway' => $gatewayName,
                    'payload' => $payload
                ]);
                
                return new PaymentResult(
                    success: false,
                    paymentStatus: 'FAILED',
                    errorMessage: 'Invalid webhook signature'
                );
            }
            
            // Extract payment information from payload
            $gatewayPaymentId = $this->extractGatewayPaymentId($gatewayName, $payload);
            $paymentStatus = $this->extractPaymentStatus($gatewayName, $payload);
            
            // Find payment record by gateway_payment_id or payment_reference
            $paymentRecord = PaymentRecord::where('gateway_payment_id', $gatewayPaymentId)
                ->orWhere('payment_reference', $gatewayPaymentId)
                ->first();
            
            if (!$paymentRecord) {
                Log::error('Payment record not found', [
                    'gateway' => $gatewayName,
                    'gateway_payment_id' => $gatewayPaymentId
                ]);
                
                return new PaymentResult(
                    success: false,
                    paymentStatus: 'FAILED',
                    errorMessage: 'Payment record not found'
                );
            }
            
            // Create new payment record with updated status (immutable ledger)
            $newPaymentData = new PaymentData(
                orgId: $paymentRecord->org_id,
                subscriptionId: $paymentRecord->subscription_id,
                paymentType: $paymentRecord->payment_type,
                paymentStatus: $paymentStatus,
                taxableAmount: (float) $paymentRecord->taxable_amount,
                cgstAmount: (float) $paymentRecord->cgst_amount,
                sgstAmount: (float) $paymentRecord->sgst_amount,
                igstAmount: (float) $paymentRecord->igst_amount,
                totalAmount: (float) $paymentRecord->total_amount,
                gatewayName: $gatewayName,
                gatewayPaymentId: $gatewayPaymentId,
                gatewayResponse: $payload
            );
            
            $newPaymentRecord = $this->recordPayment($newPaymentData);
            
            // Update payment record with reference and payment date
            PaymentRecord::where('payment_id', $newPaymentRecord->payment_id)
                ->update([
                    'payment_reference' => $paymentRecord->payment_reference,
                    'payment_date' => now()
                ]);
            
            // If payment successful, update subscription
            if ($paymentStatus === 'SUCCESS' && $paymentRecord->subscription_id) {
                $this->updateSubscriptionOnSuccess($paymentRecord->subscription_id);
            }
            
            Log::info('Payment callback processed', [
                'gateway' => $gatewayName,
                'payment_id' => $newPaymentRecord->payment_id,
                'status' => $paymentStatus
            ]);
            
            return new PaymentResult(
                success: true,
                paymentStatus: $paymentStatus,
                paymentId: $newPaymentRecord->payment_id
            );
            
        } catch (Exception $e) {
            Log::error('Payment callback processing failed', [
                'gateway' => $gatewayName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return new PaymentResult(
                success: false,
                paymentStatus: 'FAILED',
                errorMessage: $e->getMessage()
            );
        }
    }
    
    /**
     * Record payment transaction
     */
    public function recordPayment(PaymentData $data): PaymentRecord
    {
        $paymentRecord = PaymentRecord::create([
            'org_id' => $data->orgId,
            'subscription_id' => $data->subscriptionId,
            'payment_reference' => Str::uuid()->toString(), // Temporary, will be updated
            'payment_type' => $data->paymentType,
            'payment_status' => $data->paymentStatus,
            'taxable_amount' => $data->taxableAmount,
            'cgst_amount' => $data->cgstAmount,
            'sgst_amount' => $data->sgstAmount,
            'igst_amount' => $data->igstAmount,
            'total_amount' => $data->totalAmount,
            'gateway_name' => $data->gatewayName,
            'gateway_payment_id' => $data->gatewayPaymentId,
            'gateway_response' => $data->gatewayResponse,
        ]);
        
        Log::info('Payment record created', [
            'payment_id' => $paymentRecord->payment_id,
            'org_id' => $data->orgId,
            'type' => $data->paymentType,
            'status' => $data->paymentStatus,
            'amount' => $data->totalAmount
        ]);
        
        // Log payment transaction
        AuditLogger::logPaymentTransaction(
            $paymentRecord->payment_id,
            $data->orgId,
            $data->paymentType,
            $data->paymentStatus,
            $data->totalAmount,
            $data->gatewayName,
            $data->gatewayPaymentId,
            $data->subscriptionId
        );
        
        return $paymentRecord;
    }
    
    /**
     * Process refund
     */
    public function processRefund(int $originalPaymentId, float $amount, string $reason): PaymentRecord
    {
        $originalPayment = PaymentRecord::findOrFail($originalPaymentId);
        
        if (!$originalPayment->isSuccessful()) {
            throw new Exception('Can only refund successful payments');
        }
        
        if ($amount > (float) $originalPayment->total_amount) {
            throw new Exception('Refund amount cannot exceed original payment amount');
        }
        
        // Calculate proportional tax amounts
        $refundRatio = $amount / (float) $originalPayment->total_amount;
        $taxableAmount = (float) $originalPayment->taxable_amount * $refundRatio;
        $cgstAmount = (float) $originalPayment->cgst_amount * $refundRatio;
        $sgstAmount = (float) $originalPayment->sgst_amount * $refundRatio;
        $igstAmount = (float) $originalPayment->igst_amount * $refundRatio;
        
        // Create new refund record
        $refundData = new PaymentData(
            orgId: $originalPayment->org_id,
            subscriptionId: $originalPayment->subscription_id,
            paymentType: 'REFUND',
            paymentStatus: 'SUCCESS',
            taxableAmount: -$taxableAmount,
            cgstAmount: -$cgstAmount,
            sgstAmount: -$sgstAmount,
            igstAmount: -$igstAmount,
            totalAmount: -$amount,
            gatewayName: $originalPayment->gateway_name,
            gatewayPaymentId: $originalPayment->gateway_payment_id,
            gatewayResponse: ['reason' => $reason, 'original_payment_id' => $originalPaymentId]
        );
        
        $refundRecord = $this->recordPayment($refundData);
        
        // Update refund record with unique reference
        $refundReference = $this->generatePaymentReference();
        PaymentRecord::where('payment_id', $refundRecord->payment_id)
            ->update([
                'payment_reference' => $refundReference,
                'payment_date' => now()
            ]);
        
        Log::info('Refund processed', [
            'refund_id' => $refundRecord->payment_id,
            'original_payment_id' => $originalPaymentId,
            'amount' => $amount,
            'reason' => $reason
        ]);
        
        return $refundRecord->fresh();
    }
    
    /**
     * Calculate tax amounts based on country
     */
    public function calculateTax(float $taxableAmount, string $countryCode): TaxBreakdown
    {
        // India: CGST + SGST or IGST
        if ($countryCode === 'IN') {
            // For simplicity, using 9% CGST + 9% SGST = 18% total
            // In production, this would check state codes for IGST vs CGST+SGST
            $cgstAmount = round($taxableAmount * 0.09, 2);
            $sgstAmount = round($taxableAmount * 0.09, 2);
            $igstAmount = 0.0;
            
            return new TaxBreakdown($cgstAmount, $sgstAmount, $igstAmount);
        }
        
        // Other countries: no tax or different tax structure
        return new TaxBreakdown(0.0, 0.0, 0.0);
    }
    
    /**
     * Generate unique payment reference
     */
    private function generatePaymentReference(): string
    {
        return 'PAY-' . strtoupper(Str::random(12)) . '-' . time();
    }
    
    /**
     * Verify webhook signature (gateway-specific)
     */
    private function verifyWebhookSignature(string $gatewayName, array $payload): bool
    {
        // In production, implement actual signature verification
        // For now, return true for testing
        return true;
    }
    
    /**
     * Extract gateway payment ID from payload
     */
    private function extractGatewayPaymentId(string $gatewayName, array $payload): string
    {
        return match($gatewayName) {
            'razorpay' => $payload['payload']['payment']['entity']['id'] ?? '',
            'stripe' => $payload['data']['object']['id'] ?? '',
            default => ''
        };
    }
    
    /**
     * Extract payment status from payload
     */
    private function extractPaymentStatus(string $gatewayName, array $payload): string
    {
        $status = match($gatewayName) {
            'razorpay' => $payload['payload']['payment']['entity']['status'] ?? '',
            'stripe' => $payload['data']['object']['status'] ?? '',
            default => ''
        };
        
        return match($status) {
            'captured', 'succeeded', 'paid' => 'SUCCESS',
            'failed', 'canceled' => 'FAILED',
            default => 'PENDING'
        };
    }
    
    /**
     * Update subscription on successful payment
     */
    private function updateSubscriptionOnSuccess(int $subscriptionId): void
    {
        $subscription = OrgSubscription::with('plan')->findOrFail($subscriptionId);
        
        // Calculate next billing period based on billing cycle
        $currentPeriodEnd = now();
        $nextBillingDate = match($subscription->plan->billing_cycle) {
            'MONTHLY' => now()->addMonth(),
            'QUARTERLY' => now()->addMonths(3),
            'ANNUAL' => now()->addYear(),
            default => now()->addMonth()
        };
        
        $subscription->update([
            'subscription_status' => 'ACTIVE',
            'current_period_start' => $currentPeriodEnd,
            'current_period_end' => $nextBillingDate,
            'next_billing_date' => $nextBillingDate,
            'grace_period_until' => null
        ]);
        
        Log::info('Subscription updated on successful payment', [
            'subscription_id' => $subscriptionId,
            'next_billing_date' => $nextBillingDate->toDateString()
        ]);
    }
}
