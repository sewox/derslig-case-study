<?php

declare(strict_types=1);

namespace App\Services\FeeCalculator;

use App\Services\ConfigurationService;

class LowAmountFeeStrategy implements FeeCalculatorStrategy
{
    public function __construct(protected ConfigurationService $configurationService)
    {
    }

    public function calculate(float $amount): float
    {
        // Fixed Fee
        return $this->configurationService->getFloat('FEE_LOW_FIXED', 2.0);
    }
}
