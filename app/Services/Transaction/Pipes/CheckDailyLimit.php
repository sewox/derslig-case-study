<?php

declare(strict_types=1);

namespace App\Services\Transaction\Pipes;

use App\DTO\TransactionDTO;
use App\Enums\TransactionType;
use App\Repository\TransactionRepository;
use App\Services\ExchangeRateService;
use Closure;
use Exception;

use App\Services\ConfigurationService;

class CheckDailyLimit
{
    public function __construct(
        protected TransactionRepository $transactionRepository,
        protected ExchangeRateService $exchangeRateService,
        protected ConfigurationService $configurationService
    ) {
    }

    public function handle(TransactionDTO $dto, Closure $next)
    {
        if ($dto->type === TransactionType::TRANSFER) {
            $dailyLimit = $this->configurationService->getFloat('DAILY_TRANSFER_LIMIT_TRY', 50000.0);

            // Check Current Transaction Amount in TRY
            $currentAmountTry = $this->exchangeRateService->convertToTry(
                $dto->amount,
                $dto->sourceWallet->currency
            );

            if ($currentAmountTry > $dailyLimit) {
                 throw new Exception(__("messages.transaction.transaction_exceeds_limit"));
            }

            // Fetch History
            $dailyTransfers = $this->transactionRepository->getDailyTransfersForUser($dto->user->id);
            
            $totalDailyAmountTry = 0.0;
            foreach ($dailyTransfers as $txn) {
                $totalDailyAmountTry += $this->exchangeRateService->convertToTry((float)$txn->amount, $txn->currency);
            }

            if (($totalDailyAmountTry + $currentAmountTry) > $dailyLimit) {
                throw new Exception(__('messages.transaction.daily_limit_exceeded', ['limit' => $dailyLimit]));
            }
        }

        return $next($dto);
    }
}
