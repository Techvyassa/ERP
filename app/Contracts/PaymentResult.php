<?php

namespace App\Contracts;

class PaymentResult
{
    public function __construct(
        public bool $success,
        public string $paymentStatus,
        public ?int $paymentId = null,
        public ?string $errorMessage = null
    ) {}
}
