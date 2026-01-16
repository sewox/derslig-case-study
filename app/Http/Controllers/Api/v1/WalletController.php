<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class WalletController extends Controller
{
    public function __construct(protected WalletService $walletService)
    {
    }

    #[OA\Get(
        path: "/api/v1/wallets",
        operationId: "getWallets",
        summary: "List user wallets",
        tags: ["Wallets"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $wallets = $this->walletService->getUserWallets($request->user()->id);

        return response()->json([
            'data' => $wallets,
        ]);
    }

    #[OA\Get(
        path: "/api/v1/wallets/{id}",
        operationId: "getWalletById",
        summary: "Get wallet details",
        tags: ["Wallets"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Wallet ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Wallet not found")
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $wallet = $this->walletService->getWalletById($id);

        // Security: Check if wallet belongs to user
        if (! $wallet || $wallet->user_id !== auth()->id()) {
            return response()->json(['message' => __('messages.wallet.not_found')], 404);
        }

        return response()->json([
            'data' => $wallet,
        ]);
    }
}
