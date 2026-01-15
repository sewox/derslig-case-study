<?php

declare(strict_types=1);

namespace App\Services\FeeCalculator;

use InvalidArgumentException;

class FeeCalculatorContext
{
    public function calculateFee(float $amount): float
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Amount cannot be negative");
        }

        $strategy = match (true) {
            $amount <= 1000 => new LowAmountFeeStrategy(),
            $amount <= 10000 => new MediumAmountFeeStrategy(),
            default => new HighAmountFeeStrategy(),
        };

        return $strategy->calculate($amount);
    }
}
