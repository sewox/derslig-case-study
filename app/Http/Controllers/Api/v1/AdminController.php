<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repository\SuspiciousActivityRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        protected UserRepository $userRepository,
        protected TransactionRepository $transactionRepository,
        protected SuspiciousActivityRepository $suspiciousActivityRepository
    ) {
    }

    public function users(): JsonResponse
    {
        return response()->json([
            'data' => $this->userRepository->getAll(),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        // Simple pagination can be added later, for now getAll or filtered by status in Repo
        return response()->json([
            'data' => $this->transactionRepository->getAll(), 
        ]);
    }

    public function suspiciousActivities(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->suspiciousActivityRepository->getAll(),
        ]);
    }

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
