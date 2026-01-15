<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth Routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);

            // Wallets
            Route::get('wallets', [\App\Http\Controllers\Api\V1\WalletController::class, 'index']);
            Route::get('wallets/{id}', [\App\Http\Controllers\Api\V1\WalletController::class, 'show']);
            
            // Transactions
            Route::post('transactions/deposit', [\App\Http\Controllers\Api\V1\TransactionController::class, 'deposit']);
            Route::post('transactions/withdraw', [\App\Http\Controllers\Api\V1\TransactionController::class, 'withdraw']);
            Route::post('transactions/transfer', [\App\Http\Controllers\Api\V1\TransactionController::class, 'transfer']);

            // Admin Routes
            Route::middleware('App\Http\Middleware\CheckAdmin')->prefix('admin')->group(function () {
                Route::get('users', [\App\Http\Controllers\Api\V1\AdminController::class, 'users']);
                Route::get('transactions', [\App\Http\Controllers\Api\V1\AdminController::class, 'transactions']);
                Route::get('suspicious-activities', [\App\Http\Controllers\Api\V1\AdminController::class, 'suspiciousActivities']);
                Route::post('suspicious-activities/{id}/resolve', [\App\Http\Controllers\Api\V1\AdminController::class, 'resolveSuspiciousActivity']);
            });
        });
    });
});
