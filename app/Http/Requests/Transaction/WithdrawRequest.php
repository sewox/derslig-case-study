<?php

declare(strict_types=1);

namespace App\Http\Requests\Transaction;

use App\Enums\WalletCurrency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class WithdrawRequest extends FormRequest
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
        ];
    }
}
