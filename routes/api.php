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
        });
    });
});
