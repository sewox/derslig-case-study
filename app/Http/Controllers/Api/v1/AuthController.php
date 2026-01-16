<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    #[OA\Post(
        path: "/api/v1/auth/register",
        operationId: "register",
        summary: "Register new user",
        tags: ["Auth"],
        description: "Registers a new user",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "User registered successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => __('messages.auth.registered'),
            'data' => $result,
        ], 201);
    }

    #[OA\Post(
        path: "/api/v1/auth/login",
        operationId: "login",
        summary: "Login user",
        tags: ["Auth"],
        description: "Login with email and password",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login Successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Login successful"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if (! $result) {
            return response()->json([
                'message' => __('messages.auth.invalid_credentials'),
            ], 401);
        }

        return response()->json([
            'message' => __('messages.auth.login_success'),
            'data' => $result,
        ]);
    }

    #[OA\Post(
        path: "/api/v1/auth/logout",
        operationId: "logout",
        summary: "Logout user",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Logout Successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Logged out successfully")
                    ]
                )
            )
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => __('messages.auth.logout_success'),
        ]);
    }

    #[OA\Get(
        path: "/api/v1/auth/me",
        operationId: "me",
        summary: "Get current user",
        tags: ["Auth"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            )
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->load('wallets'),
        ]);
    }
}
