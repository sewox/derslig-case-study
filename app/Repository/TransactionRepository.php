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
}
