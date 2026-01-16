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

use App\Services\ConfigurationService;

class FraudCheck
{
    public function __construct(
        protected TransactionRepository $transactionRepository,
        protected ExchangeRateService $exchangeRateService,
        protected ConfigurationService $configurationService
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

        // Rule 1: Velocity Check (e.g. transfers to distinct users)
        if ($dto->type === TransactionType::TRANSFER) {
            $window = $this->configurationService->getInt('FRAUD_CHECK_VELOCITY_WINDOW_MINUTES', 60);
            $limit = $this->configurationService->getInt('FRAUD_CHECK_VELOCITY_LIMIT', 4);
            
            $uniqueRecipients = $this->transactionRepository->countUsersTransferredTo($user->id, $window);
            if ($uniqueRecipients >= $limit) {
                 $this->triggerFraction($dto, 'velocity_different_users', 'High frequency transfers to different users');
            }
        }

        // Rule 2: Night Transaction Check
        $nightStart = $this->configurationService->getInt('FRAUD_CHECK_NIGHT_START_HOUR', 2);
        $nightEnd = $this->configurationService->getInt('FRAUD_CHECK_NIGHT_END_HOUR', 6);
        $nightAmountLimit = $this->configurationService->getFloat('FRAUD_CHECK_NIGHT_AMOUNT_LIMIT', 5000);

        $hour = now()->hour;
        if ($hour >= $nightStart && $hour < $nightEnd) {
            if ($amountTry > $nightAmountLimit) {
                 $dto->status = TransactionStatus::PENDING_REVIEW;
                 SuspiciousActivityDetected::dispatch(
                    $user, 
                    'night_transaction', 
                    SuspiciousActivitySeverity::MEDIUM->value, 
                    'Large transaction during night hours'
                );
            }
        }

        // Rule 3: New Account Check
        $newAccountDays = $this->configurationService->getInt('FRAUD_CHECK_NEW_ACCOUNT_DAYS', 7);
        $newAccountAmountLimit = $this->configurationService->getFloat('FRAUD_CHECK_NEW_ACCOUNT_AMOUNT_LIMIT', 10000);

        if ($user->created_at->diffInDays(now()) < $newAccountDays) {
            if ($amountTry > $newAccountAmountLimit) {
                 $dto->status = TransactionStatus::PENDING_REVIEW;
                 SuspiciousActivityDetected::dispatch(
                    $user, 
                    'new_account_large_transaction', 
                    SuspiciousActivitySeverity::HIGH->value, 
                    'New account large transaction'
                );
            }
        }

        // Rule 4: IP Check
        if ($dto->ipAddress) {
            $ipWindow = $this->configurationService->getInt('FRAUD_CHECK_IP_WINDOW_MINUTES', 1440);
            $txns = $this->transactionRepository->getTransactionsByIp($dto->ipAddress, $ipWindow);
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
