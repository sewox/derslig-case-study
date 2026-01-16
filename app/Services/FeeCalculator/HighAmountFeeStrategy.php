<?php

declare(strict_types=1);

namespace App\Services\FeeCalculator;

use App\Services\ConfigurationService;

class HighAmountFeeStrategy implements FeeCalculatorStrategy
{
    public function __construct(protected ConfigurationService $configurationService)
    {
    }

    public function calculate(float $amount): float
    {
        // Tiered: Base + Rate on remainder
        // Total Fee = Base + ((Amount - ThresholdLow) * Rate) -- Logic in previous file was (Amount - 1000). 
        // Logic: "10,001+ TRY: Tiered: 2 TRY for first 1,000 + 0.3% on remainder"
        // Base is 2 TRY (Matches LowAmountFee). Remainder is Amount - 1000.
        // So I should fetch Base Fee and Threshold (1000) from Config.
        
        $baseFee = $this->configurationService->getFloat('FEE_HIGH_BASE_FEE', 2.0);
        $thresholdLow = $this->configurationService->getFloat('FEE_THRESHOLD_LOW', 1000.0);
        $rate = $this->configurationService->getFloat('FEE_HIGH_RATE', 0.003);

        $remainder = $amount - $thresholdLow;
        return $baseFee + ($remainder * $rate);
    }
}
