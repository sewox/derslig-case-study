<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(protected WalletService $walletService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $wallets = $this->walletService->getUserWallets($request->user()->id);

        return response()->json([
            'data' => $wallets,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $wallet = $this->walletService->getWalletById($id);

        // Security: Check if wallet belongs to user
        if (! $wallet || $wallet->user_id !== auth()->id()) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        return response()->json([
            'data' => $wallet,
        ]);
    }
}
