<?php

declare(strict_types=1);

namespace App\Services\FeeCalculator;

class MediumAmountFeeStrategy implements FeeCalculatorStrategy
{
    public function calculate(float $amount): float
    {
        // 1,001 - 10,000 TRY: 0.5%
        return $amount * 0.005;
    }
}
