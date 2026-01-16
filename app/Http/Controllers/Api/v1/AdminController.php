<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repository\SuspiciousActivityRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class AdminController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository,
        protected TransactionRepository $transactionRepository,
        protected SuspiciousActivityRepository $suspiciousActivityRepository
    ) {
    }

    #[OA\Get(
        path: "/api/v1/admin/users",
        operationId: "adminGetUsers",
        summary: "List all users",
        tags: ["Admin"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of users",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            )
        ]
    )]
    public function users(): JsonResponse
    {
        return response()->json([
            'data' => $this->userRepository->getAll(),
        ]);
    }

    #[OA\Get(
        path: "/api/v1/admin/transactions",
        operationId: "adminGetTransactions",
        summary: "List all transactions",
        tags: ["Admin"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of transactions",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            )
        ]
    )]
    public function transactions(Request $request): JsonResponse
    {
        // Simple pagination can be added later, for now getAll or filtered by status in Repo
        return response()->json([
            'data' => $this->transactionRepository->getAll(), 
        ]);
    }

    #[OA\Get(
        path: "/api/v1/admin/suspicious-activities",
        operationId: "adminGetSuspicious",
        summary: "List suspicious activities",
        tags: ["Admin"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of suspicious activities",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            )
        ]
    )]
    public function suspiciousActivities(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->suspiciousActivityRepository->getAll(),
        ]);
    }

    #[OA\Post(
        path: "/api/v1/admin/suspicious-activities/{id}/resolve",
        operationId: "adminResolveSuspicious",
        summary: "Resolve suspicious activity",
        tags: ["Admin"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "status", type: "string", example: "resolved"),
                    new OA\Property(property: "admin_note", type: "string", example: "Checked with user")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Activity updated",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function resolveSuspiciousActivity(Request $request, string $id): JsonResponse
    {
        // $request->validate(['status' => 'required|in:resolved,false_positive']);
        $status = $request->input('status', 'resolved');
        
        $updated = $this->suspiciousActivityRepository->update($id, [
            'status' => $status, // should use Enum validation
            'admin_note' => $request->input('admin_note'),
        ]);

        if (! $updated) {
            return response()->json(['message' => 'Activity not found'], 404);
        }

        return response()->json(['message' => 'Activity status updated']);
    }
}
