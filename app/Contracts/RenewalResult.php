<?php

namespace App\Contracts;

use App\Models\Control\OrgSubscription;

class RenewalResult
{
    public function __construct(
        public bool $success,
        public ?OrgSubscription $subscription = null,
        public ?string $errorMessage = null,
        public ?string $paymentStatus = null
    ) {}
}
