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

    public function countUsersTransferredTo(string $userId, int $minutes = 60): int
    {
        return $this->model->newQuery()
            ->whereHas('sourceWallet', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('type', \App\Enums\TransactionType::TRANSFER)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->distinct('target_wallet_id') // Distinct wallets usually implies distinct users if 1 wallet per currency. 
            // Better to check targetWallet -> user_id distinctness via join but wallets are unique per user-currency. 
            // Let's assume distinct target_wallet_id is good proxy or we do join.
            // Requirement: "5 transfers to different users".
            // If User A has TRY and USD wallets, transferring to both counts as 1 user?
            // "different users". So we should count unique user_ids of target wallets.
            ->join('wallets as target_wallets', 'transactions.target_wallet_id', '=', 'target_wallets.id')
            ->distinct('target_wallets.user_id')
            ->count('target_wallets.user_id');
    }

    public function getTransactionsByIp(string $ipAddress, int $minutes = 60)
    {
        return $this->model->newQuery()
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->with('sourceWallet.user')
            ->get();
    }
}
