<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\SuspiciousActivity;

class SuspiciousActivityRepository extends BaseRepository
{
    public function model(): string
    {
        return SuspiciousActivity::class;
    }
}
