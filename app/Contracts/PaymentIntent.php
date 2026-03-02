<?php

namespace App\Contracts;

class PaymentIntent
{
    public function __construct(
        public int $subscriptionId,
        public string $gatewayName,
        public string $gatewayPaymentId,
        public float $amount,
        public string $currency,
        public array $gatewayResponse
    ) {}
}
