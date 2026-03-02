<?php

namespace App\Contracts;

class PaymentData
{
    public function __construct(
        public int $orgId,
        public ?int $subscriptionId,
        public string $paymentType,
        public string $paymentStatus,
        public float $taxableAmount,
        public float $cgstAmount,
        public float $sgstAmount,
        public float $igstAmount,
        public float $totalAmount,
        public ?string $gatewayName = null,
        public ?string $gatewayPaymentId = null,
        public ?array $gatewayResponse = null
    ) {}
}
