<?php

declare(strict_types=1);

namespace App\Http\Requests\Transaction;

use App\Enums\WalletCurrency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', new Enum(WalletCurrency::class)],
            'target_user_id' => ['nullable', 'exists:users,id', 'different:user_id'], // Or target wallet ID?
            // "Transfers (Between users or own wallets)"
            // If between users, we need target user ID. If own wallets, maybe target currency?
            // "Transfer to another user" implies Target User + Currency.
            // Let's assume input is Target User ID (email?) or Wallet ID.
            // Requirement says "User can have multiple wallets".
            // Let's rely on Target Wallet ID for precision, or Target User + Currency.
            // Target User + Currency is friendlier.
            // But let's support Target Wallet ID if provided, or Target User specific.
            // Let's stick to Target User ID for "User to User" transfer.
            // If internal transfer (Currency Swap?), prompt doesn't strictly say swaps.
            // "Money Transfer API". usually User A -> User B.
            // "Transfers (Between users or own wallets)" -> Own wallets means Swap? Or moving TRY to TRY (same wallet?).
            // Let's implement User-to-User for now.
            'target_user_email' => ['required', 'email', 'exists:users,email'], 
        ];
    }
}
