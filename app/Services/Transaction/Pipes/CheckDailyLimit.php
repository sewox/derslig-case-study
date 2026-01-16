<?php

declare(strict_types=1);

namespace App\Services\Transaction\Pipes;

use App\DTO\TransactionDTO;
use App\Enums\TransactionType;
use App\Repository\TransactionRepository;
use App\Services\ExchangeRateService;
use Closure;
use Exception;

class CheckDailyLimit
{
    protected const DAILY_LIMIT_TRY = 500000.0;

    public function __construct(
        protected TransactionRepository $transactionRepository,
        protected ExchangeRateService $exchangeRateService
    ) {
    }

    public function handle(TransactionDTO $dto, Closure $next)
    {
        if ($dto->type === TransactionType::TRANSFER) {
            // Check Current Transaction Amount in TRY
            $currentAmountTry = $this->exchangeRateService->convertToTry(
                $dto->amount,
                $dto->sourceWallet->currency
            );

            if ($currentAmountTry > self::DAILY_LIMIT_TRY) {
                 throw new Exception(__("messages.transaction.transaction_exceeds_limit"));
            }

            // Fetch History
            $dailyTransfers = $this->transactionRepository->getDailyTransfersForUser($dto->user->id);
            
            $totalDailyAmountTry = 0.0;
            foreach ($dailyTransfers as $txn) {
                // $txn->currency is Enum in Model cast, but here via Eloquent it might be object or string depending on cast. 
                // Transaction Model has cast: 'currency' => WalletCurrency::class
                $totalDailyAmountTry += $this->exchangeRateService->convertToTry((float)$txn->amount, $txn->currency);
            }

            if (($totalDailyAmountTry + $currentAmountTry) > self::DAILY_LIMIT_TRY) {
                throw new Exception(__('messages.transaction.daily_limit_exceeded', ['limit' => self::DAILY_LIMIT_TRY]));
            }
        }

        return $next($dto);
    }
}
