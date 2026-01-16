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

use OpenApi\Attributes as OA;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected WalletService $walletService
    ) {
    }

    #[OA\Post(
        path: "/api/v1/transactions/deposit",
        operationId: "deposit",
        summary: "Deposit funds",
        tags: ["Transactions"],
        description: "Deposit funds into a wallet",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount", "wallet_id"],
                properties: [
                    new OA\Property(property: "amount", type: "number", format: "float", example: 100.50),
                    new OA\Property(property: "wallet_id", type: "string", format: "uuid", example: "uuid-string-here")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Deposit Successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Deposit successful"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Error"),
            new OA\Response(response: 404, description: "Wallet not found")
        ]
    )]
    public function deposit(DepositRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $wallet = $this->walletService->getWalletById($request->wallet_id);

        if (! $wallet || $wallet->user_id !== $user->id) {
            return response()->json(['message' => __('messages.wallet.not_found_or_access')], 404);
        }

        try {
            $dto = new TransactionDTO(
                user: $user,
                sourceWallet: null,
                targetWallet: $wallet,
                amount: (float) $request->amount,
                type: TransactionType::DEPOSIT,
                description: 'Deposit via API'
            );

            $transaction = $this->transactionService->processTransaction($dto);

            return response()->json([
                'message' => __('messages.transaction.deposit_success'),
                'data' => $transaction,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Post(
        path: "/api/v1/transactions/withdraw",
        operationId: "withdraw",
        summary: "Withdraw funds",
        tags: ["Transactions"],
        description: "Withdraw funds from a wallet",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount", "wallet_id"],
                properties: [
                    new OA\Property(property: "amount", type: "number", format: "float", example: 50.00),
                    new OA\Property(property: "wallet_id", type: "string", format: "uuid", example: "uuid-string-here")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Withdraw Successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Withdrawal successful"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Error or Insufficient Funds"),
            new OA\Response(response: 404, description: "Wallet not found")
        ]
    )]
    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $wallet = $this->walletService->getWalletById($request->wallet_id);

        if (! $wallet || $wallet->user_id !== $user->id) {
            return response()->json(['message' => __('messages.wallet.not_found_or_access')], 404);
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
                'message' => __('messages.transaction.withdraw_success'),
                'data' => $transaction,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    #[OA\Post(
        path: "/api/v1/transactions/transfer",
        operationId: "transfer",
        summary: "Transfer funds",
        tags: ["Transactions"],
        description: "Transfer funds to another user",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount", "currency", "target_user_email"],
                properties: [
                    new OA\Property(property: "amount", type: "number", format: "float", example: 10.00),
                    new OA\Property(property: "currency", type: "string", example: "TRY"),
                    new OA\Property(property: "target_user_email", type: "string", format: "email", example: "jane@example.com")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Transfer Successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Transfer successful"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Error"),
            new OA\Response(response: 404, description: "User or Wallet not found")
        ]
    )]
    public function transfer(TransferRequest $request): JsonResponse
    {
        $user = $request->user();
        $currency = WalletCurrency::from($request->currency);
        
        // Source Wallet
        $sourceWallet = $this->walletService->getUserWallets($user->id)
            ->where('currency', $currency)
            ->first();

        if (! $sourceWallet) {
            return response()->json(['message' => __('messages.wallet.source_not_found')], 404);
        }

        // Target User & Wallet
        // Assuming email lookup for simplicity as per Request
        $targetUser = User::where('email', $request->target_user_email)->first();
        
        if (! $targetUser) {
            return response()->json(['message' => __('messages.wallet.target_user_not_found')], 404);
        }
        
        if ($targetUser->id === $user->id) {
             return response()->json(['message' => __('messages.transaction.cannot_transfer_self')], 400);
        }

        $targetWallet = $this->walletService->getUserWallets($targetUser->id)
            ->where('currency', $currency)
            ->first();

        if (! $targetWallet) {
            // Auto-create? Or fail?
            return response()->json(['message' => __('messages.wallet.target_no_currency')], 400);
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
                'message' => __('messages.transaction.transfer_success'),
                'data' => $transaction,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
