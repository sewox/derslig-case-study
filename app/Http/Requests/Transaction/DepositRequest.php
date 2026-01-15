<?php

declare(strict_types=1);

namespace App\Http\Requests\Transaction;

use App\Enums\WalletCurrency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'wallet_id' => ['required', 'uuid', 'exists:wallets,id'],
        ];
    }
}
