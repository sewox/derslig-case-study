<?php

declare(strict_types=1);

namespace App\Services\FeeCalculator;

use App\Services\ConfigurationService;

class MediumAmountFeeStrategy implements FeeCalculatorStrategy
{
    public function __construct(protected ConfigurationService $configurationService)
    {
    }

    public function calculate(float $amount): float
    {
        // Rate based fee
        $rate = $this->configurationService->getFloat('FEE_MEDIUM_RATE', 0.005);
        return $amount * $rate;
    }
}
