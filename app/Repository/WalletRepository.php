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
}
