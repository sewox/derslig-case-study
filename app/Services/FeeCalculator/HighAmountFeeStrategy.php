<?php

declare(strict_types=1);

namespace App\Services\FeeCalculator;

class HighAmountFeeStrategy implements FeeCalculatorStrategy
{
    public function calculate(float $amount): float
    {
        // 10,001+ TRY: Tiered: 2 TRY for first 1,000 + 0.3% on remainder
        // Total Fee = 2 + ((Amount - 1000) * 0.003)
        $remainder = $amount - 1000;
        return 2.0 + ($remainder * 0.003);
    }
}
