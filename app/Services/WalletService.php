<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Wallet;
use App\Repository\WalletRepository;
use Illuminate\Database\Eloquent\Collection;

class WalletService extends BaseService
{
    public function __construct(protected WalletRepository $walletRepository)
    {
        parent::__construct($walletRepository);
    }

    public function getUserWallets(string $userId): Collection
    {
        return $this->walletRepository->getWalletsByUserId($userId);
    }

    public function getWalletById(string $id): ?Wallet
    {
        return $this->walletRepository->get($id);
    }
}
