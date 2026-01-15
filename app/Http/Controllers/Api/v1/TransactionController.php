<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTO\TransactionDTO;
use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\DepositRequest;
use App\Http\Requests\Transaction\TransferRequest;
use App\Http\Requests\Transaction\WithdrawRequest;
use App\Models\User;
use App\Services\TransactionService;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected WalletService $walletService
    ) {
    }

    public function deposit(DepositRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $wallet = $this->walletService->getWalletById($request->wallet_id);

        if (! $wallet || $wallet->user_id !== $user->id) {
            return response()->json(['message' => 'Wallet not found or does not belong to user'], 404);
        }

        try {
            $dto = new TransactionDTO(
                user: $user,
                sourceWallet: $wallet, // For Deposit, source is treated as the wallet receiving funds in our update logic override? 
                // Wait, TransactionService logic for UpdateBalances:
                // if Deposit: sourceWallet->increment.
                // So yes, pass it as sourceWallet (or rethink DTO naming).
                targetWallet: null,
                amount: (float) $request->amount,
                type: TransactionType::DEPOSIT,
                description: 'Deposit via API'
            );

            $transaction = $this->transactionService->processTransaction($dto);

            return response()->json([
                'message' => 'Deposit successful',
                'data' => $transaction,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        $user = $request->user();
        $currency = WalletCurrency::from($request->currency);
        
        $wallet = $this->walletService->getUserWallets($user->id)
            ->where('currency', $currency)
            ->first();

        if (! $wallet) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        try {
            $dto = new TransactionDTO(
                user: $user,
                sourceWallet: $wallet,
                targetWallet: null,
                amount: (float) $request->amount,
                type: TransactionType::WITHDRAW,
                description: 'Withdrawal via API'
            );

            $transaction = $this->transactionService->processTransaction($dto);

            return response()->json([
                'message' => 'Withdrawal successful',
                'data' => $transaction,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function transfer(TransferRequest $request): JsonResponse
    {
        $user = $request->user();
        $currency = WalletCurrency::from($request->currency);
        
        // Source Wallet
        $sourceWallet = $this->walletService->getUserWallets($user->id)
            ->where('currency', $currency)
            ->first();

        if (! $sourceWallet) {
            return response()->json(['message' => 'Source wallet not found'], 404);
        }

        // Target User & Wallet
        // Assuming email lookup for simplicity as per Request
        $targetUser = User::where('email', $request->target_user_email)->first();
        
        if (! $targetUser) {
            return response()->json(['message' => 'Target user not found'], 404);
        }
        
        if ($targetUser->id === $user->id) {
             return response()->json(['message' => 'Cannot transfer to yourself'], 400);
        }

        $targetWallet = $this->walletService->getUserWallets($targetUser->id)
            ->where('currency', $currency)
            ->first();

        if (! $targetWallet) {
            // Auto-create? Or fail?
            return response()->json(['message' => 'Target user does not have a wallet for this currency'], 400);
        }

        try {
            $dto = new TransactionDTO(
                user: $user,
                sourceWallet: $sourceWallet,
                targetWallet: $targetWallet,
                amount: (float) $request->amount,
                type: TransactionType::TRANSFER,
                description: 'Transfer to ' . $targetUser->email
            );

            $transaction = $this->transactionService->processTransaction($dto);

            return response()->json([
                'message' => 'Transfer successful',
                'data' => $transaction,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
