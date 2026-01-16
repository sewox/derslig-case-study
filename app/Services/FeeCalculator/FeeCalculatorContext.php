<?php

declare(strict_types=1);

namespace App\Services\FeeCalculator;

use App\Services\ConfigurationService;
use InvalidArgumentException;

class FeeCalculatorContext
{
    public function __construct(protected ConfigurationService $configurationService)
    {
    }

    public function calculateFee(float $amount): float
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Amount cannot be negative");
        }

        $thresholdLow = $this->configurationService->getFloat('FEE_THRESHOLD_LOW', 1000.0);
        $thresholdMedium = $this->configurationService->getFloat('FEE_THRESHOLD_MEDIUM', 10000.0);

        // Fetch config once to pass to strategies, or let strategies fetch it if they have access.
        // Strategies are simple classes here. I can pass config values to constructors.
        
        $strategy = match (true) {
            $amount <= $thresholdLow => new LowAmountFeeStrategy($this->configurationService),
            $amount <= $thresholdMedium => new MediumAmountFeeStrategy($this->configurationService),
            default => new HighAmountFeeStrategy($this->configurationService),
        };

        return $strategy->calculate($amount);
    }
}
