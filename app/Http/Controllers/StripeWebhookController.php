<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentProcessingService;
use App\Services\Gateways\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class StripeWebhookController extends Controller
{
    public function __construct(
        private PaymentProcessingService $paymentService
    ) {}
    
    /**
     * Handle Stripe webhook callback
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            // Get webhook signature from header
            $signature = $request->header('Stripe-Signature', '');
            $payload = $request->all();
            
            Log::info('Stripe webhook received', [
                'type' => $payload['type'] ?? 'unknown',
                'payload_keys' => array_keys($payload)
            ]);
            
            // Verify webhook signature
            $gateway = PaymentGatewayFactory::create('stripe');
            if (!$gateway->verifyWebhookSignature($payload, $signature)) {
                Log::warning('Stripe webhook signature verification failed', [
                    'signature' => $signature
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 401);
            }
            
            // Process webhook based on event type
            $eventType = $payload['type'] ?? '';
            
            if (in_array($eventType, [
                'payment_intent.succeeded',
                'payment_intent.payment_failed',
                'payment_intent.canceled'
            ])) {
                // Process payment callback
                $result = $this->paymentService->processCallback('stripe', $payload);
                
                if ($result->success) {
                    Log::info('Stripe webhook processed successfully', [
                        'type' => $eventType,
                        'payment_id' => $result->paymentId,
                        'status' => $result->paymentStatus
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Webhook processed successfully'
                    ], 200);
                } else {
                    Log::error('Stripe webhook processing failed', [
                        'type' => $eventType,
                        'error' => $result->errorMessage
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => $result->errorMessage
                    ], 400);
                }
            }
            
            // For other events, just acknowledge receipt
            Log::info('Stripe webhook acknowledged', [
                'type' => $eventType
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook received'
            ], 200);
            
        } catch (Exception $e) {
            Log::error('Stripe webhook handling error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }
}
