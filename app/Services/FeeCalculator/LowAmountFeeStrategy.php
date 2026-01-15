<?php

declare(strict_types=1);

namespace App\Services\FeeCalculator;

class LowAmountFeeStrategy implements FeeCalculatorStrategy
{
    public function calculate(float $amount): float
    {
        // 0 - 1,000 TRY: Fixed 2 TRY
        return 2.0;
    }
}
