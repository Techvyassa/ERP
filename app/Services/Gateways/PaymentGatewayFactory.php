<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGateway;
use Exception;

class PaymentGatewayFactory
{
    /**
     * Create payment gateway instance
     * @param string $gatewayName
     * @return PaymentGateway
     * @throws Exception
     */
    public static function create(string $gatewayName): PaymentGateway
    {
        return match(strtolower($gatewayName)) {
            'razorpay' => new RazorpayGateway(),
            'stripe' => new StripeGateway(),
            default => throw new Exception("Unsupported payment gateway: {$gatewayName}")
        };
    }
    
    /**
     * Get list of supported gateways
     * @return array
     */
    public static function getSupportedGateways(): array
    {
        return ['razorpay', 'stripe'];
    }
    
    /**
     * Check if gateway is supported
     * @param string $gatewayName
     * @return bool
     */
    public static function isSupported(string $gatewayName): bool
    {
        return in_array(strtolower($gatewayName), self::getSupportedGateways());
    }
}
