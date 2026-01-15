<?php

declare(strict_types=1);

namespace App\Enums;

enum SuspiciousActivityStatus: string
{
    case PENDING = 'pending';
    case INVESTIGATING = 'investigating';
    case RESOLVED = 'resolved';
    case FALSE_POSITIVE = 'false_positive';
}
