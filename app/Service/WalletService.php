<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\WalletRepository;

class WalletService extends BaseService
{
    /**
     * WalletService constructor.
     */
    public function __construct(WalletRepository $repository)
    {
        parent::__construct($repository);
    }
}
