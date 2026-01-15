<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\User;
use App\Models\Wallet;
use App\Enums\TransactionType;

class TransactionDTO
{
    public function __construct(
        public readonly User $user,
        public readonly ?Wallet $sourceWallet,
        public readonly ?Wallet $targetWallet,
        public readonly float $amount,
        public readonly TransactionType $type,
        public float $fee = 0.0,
        public ?string $description = null,
        public ?string $ipAddress = null,
    ) {
    }
}
