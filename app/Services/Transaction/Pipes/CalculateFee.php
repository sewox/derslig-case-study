<?php

declare(strict_types=1);

namespace App\Services\Transaction\Pipes;

use App\DTO\TransactionDTO;
use App\Services\FeeCalculator\FeeCalculatorContext;
use Closure;

class CalculateFee
{
    public function __construct(protected FeeCalculatorContext $feeCalculator)
    {
    }

    public function handle(TransactionDTO $dto, Closure $next)
    {
        // Calculate fee only for transfers as per requirement "Fees are charged on transfer transactions"
        // But maybe Withdraw also has fee? The prompt table says "Transaction Amount | Fee Type" under "Fee Structure".
        // It says "Fees are charged on transfer transactions:". Explicitly says "transfer".
        // So Deposit/Withdraw might be free or logic is generic. I will assume only Transfer for now or apply to all if configured.
        // Prompt says: "Fees are charged on transfer transactions:"
        
        if ($dto->type === \App\Enums\TransactionType::TRANSFER) {
            $dto->fee = $this->feeCalculator->calculateFee($dto->amount);
        }

        return $next($dto);
    }
}
