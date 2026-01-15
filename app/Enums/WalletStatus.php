<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletStatus: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
}
