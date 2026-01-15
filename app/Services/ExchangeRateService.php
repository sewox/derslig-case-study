<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\WalletCurrency;

class ExchangeRateService
{
    // Base Currency: TRY
    // Rates are Example values
    protected array $rates = [
        'TRY' => 1.0,
        'USD' => 34.50,
        'EUR' => 36.20,
    ];

    public function convertToTry(float $amount, WalletCurrency $currency): float
    {
        $rate = $this->rates[$currency->value] ?? 1.0;
        return $amount * $rate;
    }
}
