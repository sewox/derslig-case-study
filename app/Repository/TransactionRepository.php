<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Transaction;

class TransactionRepository extends BaseRepository
{
    public function model(): string
    {
        return Transaction::class;
    }

    public function getDailyTransfersForUser(string $userId)
    {
        return $this->model->newQuery()
            ->whereHas('sourceWallet', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('type', \App\Enums\TransactionType::TRANSFER)
            ->where('created_at', '>=', now()->startOfDay())
            ->get(['amount', 'currency']);
    }
}
