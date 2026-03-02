<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentProcessingService;
use App\Services\Gateways\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class RazorpayWebhookController extends Controller
{
    public function __construct(
        private PaymentProcessingService $paymentService
    ) {}
    
    /**
     * Handle Razorpay webhook callback
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            // Get webhook signature from header
            $signature = $request->header('X-Razorpay-Signature', '');
            $payload = $request->all();
            
            Log::info('Razorpay webhook received', [
                'event' => $payload['event'] ?? 'unknown',
                'payload_keys' => array_keys($payload)
            ]);
            
            // Verify webhook signature
            $gateway = PaymentGatewayFactory::create('razorpay');
            if (!$gateway->verifyWebhookSignature($payload, $signature)) {
                Log::warning('Razorpay webhook signature verification failed', [
                    'signature' => $signature
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 401);
            }
            
            // Process webhook based on event type
            $event = $payload['event'] ?? '';
            
            if (in_array($event, ['payment.captured', 'payment.failed', 'payment.authorized'])) {
                // Process payment callback
                $result = $this->paymentService->processCallback('razorpay', $payload);
                
                if ($result->success) {
                    Log::info('Razorpay webhook processed successfully', [
                        'event' => $event,
                        'payment_id' => $result->paymentId,
                        'status' => $result->paymentStatus
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Webhook processed successfully'
                    ], 200);
                } else {
                    Log::error('Razorpay webhook processing failed', [
                        'event' => $event,
                        'error' => $result->errorMessage
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => $result->errorMessage
                    ], 400);
                }
            }
            
            // For other events, just acknowledge receipt
            Log::info('Razorpay webhook acknowledged', [
                'event' => $event
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook received'
            ], 200);
            
        } catch (Exception $e) {
            Log::error('Razorpay webhook handling error', [
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
