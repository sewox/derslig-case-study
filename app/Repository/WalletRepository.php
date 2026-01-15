<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Wallet;

class WalletRepository extends BaseRepository
{
    public function model(): string
    {
        return Wallet::class;
    }

    public function getWalletsByUserId(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->newQuery()->where('user_id', $userId)->get();
    }
}
