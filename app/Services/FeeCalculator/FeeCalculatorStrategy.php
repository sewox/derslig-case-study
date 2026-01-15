<?php

declare(strict_types=1);

namespace App\Services\FeeCalculator;

interface FeeCalculatorStrategy
{
    public function calculate(float $amount): float;
}
