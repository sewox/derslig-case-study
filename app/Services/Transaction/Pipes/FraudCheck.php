<?php

declare(strict_types=1);

namespace App\Services\Transaction\Pipes;

use App\DTO\TransactionDTO;
use App\Enums\SuspiciousActivitySeverity;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\SuspiciousActivityDetected;
use App\Repository\TransactionRepository;
use App\Services\ExchangeRateService;
use Closure;
use Carbon\Carbon;

class FraudCheck
{
    public function __construct(
        protected TransactionRepository $transactionRepository,
        protected ExchangeRateService $exchangeRateService
    ) {
    }

    public function handle(TransactionDTO $dto, Closure $next)
    {
        // Skip fraud check for internal system actions if needed, but requirements say "before each transaction".
        
        $user = $dto->user;
        $amountTry = $this->exchangeRateService->convertToTry(
            $dto->amount, 
            $dto->sourceWallet?->currency ?? $dto->targetWallet?->currency // Depending on context, conversion base
        );

        // Rule 1: 5+ transfers to different users within 1 hour
        if ($dto->type === TransactionType::TRANSFER) {
            $uniqueRecipients = $this->transactionRepository->countUsersTransferredTo($user->id, 60);
            // Including current one? countUsersTransferredTo checks history. 
            // If accumulated history >= 4, adding this one makes 5.
            if ($uniqueRecipients >= 4) {
                 $this->triggerFraction($dto, 'velocity_different_users', 'High frequency transfers to different users');
            }
        }

        // Rule 2: 5000+ TRY transaction between 02:00-06:00
        $hour = now()->hour;
        if ($hour >= 2 && $hour < 6) {
            if ($amountTry > 5000) {
                // Require manual approval
                 $dto->status = TransactionStatus::PENDING_REVIEW;
                 // Event triggered? Table says "Appropriate event should be triggered".
                 SuspiciousActivityDetected::dispatch(
                    $user, 
                    'night_transaction', 
                    SuspiciousActivitySeverity::MEDIUM->value, 
                    'Large transaction during night hours'
                );
            }
        }

        // Rule 3: New account (7 days) + 10,000+ TRY
        if ($user->created_at->diffInDays(now()) < 7) {
            if ($amountTry > 10000) {
                 $dto->status = TransactionStatus::PENDING_REVIEW;
                 SuspiciousActivityDetected::dispatch(
                    $user, 
                    'new_account_large_transaction', 
                    SuspiciousActivitySeverity::HIGH->value, 
                    'New account large transaction'
                );
            }
        }

        // Rule 4: Transactions from same IP with different accounts
        if ($dto->ipAddress) {
            // Check past transactions from this IP in last 24 hours maybe?
            // "Transactions from same IP with different accounts" - Notify admin.
            // If I find *any* transaction from this IP belonging to *another* user.
            $txns = $this->transactionRepository->getTransactionsByIp($dto->ipAddress, 1440); // 24 hours
            $otherUsers = $txns->pluck('sourceWallet.user.id')->unique()->reject(fn($id) => $id === $user->id);
            
            if ($otherUsers->isNotEmpty()) {
                 SuspiciousActivityDetected::dispatch(
                    $user, 
                    'ip_mismatch', 
                    SuspiciousActivitySeverity::CRITICAL->value, 
                    'Multiple accounts using same IP: ' . $dto->ipAddress
                );
            }
        }

        return $next($dto);
    }

    protected function triggerFraction(TransactionDTO $dto, string $rule, string $details): void
    {
         // "Trigger SuspiciousActivityDetected event"
         SuspiciousActivityDetected::dispatch(
            $dto->user, 
            $rule, 
            SuspiciousActivitySeverity::HIGH->value, 
            $details
        );
         
         // Should we block? The table says "Trigger SuspiciousActivityDetected event".
         // It doesn't explicitly say "Block" or "Pending Review" for "5+ transfers".
         // But usually this implies review. I'll set to Pending Review to be safe or just log.
         // Table: "Action: Trigger SuspiciousActivityDetected event". 
         // For others it says "Require manual approval".
         // So for 5+ transfers, maybe just event.
    }
}
