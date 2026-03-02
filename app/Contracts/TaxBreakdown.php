<?php

namespace App\Contracts;

class TaxBreakdown
{
    public float $cgstAmount = 0.0;
    public float $sgstAmount = 0.0;
    public float $igstAmount = 0.0;
    public float $totalTax = 0.0;

    public function __construct(
        float $cgstAmount = 0.0,
        float $sgstAmount = 0.0,
        float $igstAmount = 0.0
    ) {
        $this->cgstAmount = $cgstAmount;
        $this->sgstAmount = $sgstAmount;
        $this->igstAmount = $igstAmount;
        $this->totalTax = $cgstAmount + $sgstAmount + $igstAmount;
    }
}
