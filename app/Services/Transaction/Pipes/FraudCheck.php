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
        $user = $dto->user;
        
        $params = $this->getAmountAndCurrency($dto);
        $amountTry = $this->exchangeRateService->convertToTry(
            $params['amount'], 
            $params['currency']
        );

        // Rule 1: Velocity Check
        if ($dto->type === TransactionType::TRANSFER) {
            $window = $this->configurationService->getInt('FRAUD_CHECK_VELOCITY_WINDOW_MINUTES', 60);
            $limit = $this->configurationService->getInt('FRAUD_CHECK_VELOCITY_LIMIT', 4);
            
            // Check count of THIS user's outbound transfers to distinct recipients
            // We need a lightweight check. The repo method counts distinct recipients.
            // But for simple velocity (volume), user might want simple count of transfers?
            // Requirement: "5 transfers to different users". 
            // So recipient count is correct.
            $uniqueRecipients = $this->transactionRepository->countUsersTransferredTo($user->id, $window);
            
            // Since we are *about* to do one, we check if current count >= limit
            if ($uniqueRecipients >= $limit) {
                 $this->blockWalletAndThrow($dto, 'velocity_limit_exceeded', 'High frequency transfers');
            }
        }

        // Rule 2: Night Transaction Check
        $nightStart = $this->configurationService->getInt('FRAUD_CHECK_NIGHT_START_HOUR', 2);
        $nightEnd = $this->configurationService->getInt('FRAUD_CHECK_NIGHT_END_HOUR', 6);
        $nightAmountLimit = $this->configurationService->getFloat('FRAUD_CHECK_NIGHT_AMOUNT_LIMIT', 5000);

        $hour = now()->hour;
        if ($hour >= $nightStart && $hour < $nightEnd) {
            if ($amountTry > $nightAmountLimit) {
                 $this->blockWalletAndThrow($dto, 'night_transaction_limit', 'Large transaction during night hours');
            }
        }

        // Rule 3: New Account Check
        $newAccountDays = $this->configurationService->getInt('FRAUD_CHECK_NEW_ACCOUNT_DAYS', 7);
        $newAccountAmountLimit = $this->configurationService->getFloat('FRAUD_CHECK_NEW_ACCOUNT_AMOUNT_LIMIT', 10000);

        if ($user->created_at->diffInDays(now()) < $newAccountDays) {
            if ($amountTry > $newAccountAmountLimit) {
                // For test expectation: this should likely block or throw
                $this->blockWalletAndThrow($dto, 'new_account_high_amount', 'New account large transaction');
            }
        }

        // Rule 4: IP Check (Dispatch only)
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

    private function getAmountAndCurrency(TransactionDTO $dto): array
    {
        if ($dto->sourceWallet) {
            return ['amount' => $dto->amount, 'currency' => $dto->sourceWallet->currency];
        }
        if ($dto->targetWallet) {
            return ['amount' => $dto->amount, 'currency' => $dto->targetWallet->currency];
        }
        // Default fallbacks?
        return ['amount' => $dto->amount, 'currency' => \App\Enums\WalletCurrency::TRY];
    }

    private function blockWalletAndThrow(TransactionDTO $dto, string $rule, string $details): void
    {
        // 1. Dispatch Event
        SuspiciousActivityDetected::dispatch(
            $dto->user, 
            $rule, 
            SuspiciousActivitySeverity::HIGH->value, 
            $details
        );

        // 2. Block Wallet
        if ($dto->sourceWallet) {
            $dto->sourceWallet->update([
                'status' => 'blocked',
                'blocked_reason' => 'Fraud Detection: ' . $rule
            ]);
        }

        // 3. Throw Exception to Stop Transaction
        throw new \Exception(__('messages.transaction.fraud.' . $rule));
    }
}
