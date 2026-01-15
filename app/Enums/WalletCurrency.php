<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletCurrency: string
{
    case TRY = 'TRY';
    case USD = 'USD';
    case EUR = 'EUR';
}
