<?php

declare(strict_types=1);

namespace App\Services\Transaction\Pipes;

use App\DTO\TransactionDTO;
use App\Enums\TransactionType;
use Closure;
use InvalidArgumentException;

class CheckInsufficientBalance
{
    public function handle(TransactionDTO $dto, Closure $next)
    {
        // Only for outgoing transactions
        if (in_array($dto->type, [TransactionType::WITHDRAW, TransactionType::TRANSFER])) {
            if (!$dto->sourceWallet) {
                throw new InvalidArgumentException("Source wallet is required for this transaction type");
            }
            
            $totalRequired = $dto->amount + $dto->fee;
            
            if ($dto->sourceWallet->balance < $totalRequired) {
                // Since this runs before DB transaction or maybe inside, we can throw exception
                // Custom exception is better 'InsufficientBalanceException'
                throw new InvalidArgumentException("Insufficient Balance");
            }
        }

        return $next($dto);
    }
}
