<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\TransactionDTO;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Repository\TransactionRepository;
use App\Repository\WalletRepository;
use App\Services\Transaction\Pipes\CalculateFee;
use App\Services\Transaction\Pipes\CheckInsufficientBalance;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use App\Enums\TransactionType;
use Exception;

class TransactionService extends BaseService
{
    public function __construct(
        protected TransactionRepository $transactionRepository,
        protected WalletRepository $walletRepository,
        protected Pipeline $pipeline
    ) {
        parent::__construct($transactionRepository);
    }

    public function processTransaction(TransactionDTO $dto): Transaction
    {
        return DB::transaction(function () use ($dto) {
            // 1. Run Pipeline (Validation, Fee Calculation, Rules)
            /** @var TransactionDTO $processedDto */
            $processedDto = $this->pipeline
                ->send($dto)
                ->through([
                    CheckInsufficientBalance::class,
                    CalculateFee::class,
                    // TODO: Add FraudCheck pipe later
                ])
                ->thenReturn();

            // 2. Create Transaction Record
            // Determine active wallet for currency context
            $activeWallet = $processedDto->sourceWallet ?? $processedDto->targetWallet;

            $transaction = $this->transactionRepository->create([
                'source_wallet_id' => $processedDto->sourceWallet?->id,
                'target_wallet_id' => $processedDto->targetWallet?->id,
                'amount' => $processedDto->amount,
                'fee' => $processedDto->fee,
                'currency' => $activeWallet->currency, 
                'type' => $processedDto->type,
                'status' => TransactionStatus::COMPLETED,
                'description' => $processedDto->description,
                'ip_address' => $processedDto->ipAddress,
                'performed_at' => now(),
            ]);

            // 3. Update Balances
            $this->updateBalances($processedDto);

            return $transaction;
        });
    }

    protected function updateBalances(TransactionDTO $dto): void
    {
        // Deduct from Source (Amount + Fee)
        if ($dto->sourceWallet && in_array($dto->type, [TransactionType::WITHDRAW, TransactionType::TRANSFER])) {
             $totalDeduction = $dto->amount + $dto->fee;
             $dto->sourceWallet->decrement('balance', $totalDeduction);
        }

        // Add to Target (Amount only)
        if ($dto->targetWallet && in_array($dto->type, [TransactionType::DEPOSIT, TransactionType::TRANSFER])) {
             $dto->targetWallet->increment('balance', $dto->amount);
        }
    }
}
