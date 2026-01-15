<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Enums\UserRole;
use App\Enums\WalletCurrency;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Repository\UserRepository;
use App\Repository\WalletRepository;

class AuthService extends BaseService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected WalletRepository $walletRepository
    ) {
        parent::__construct($userRepository);
    }

    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            /** @var User $user */
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? UserRole::USER,
            ]);

            // Create default wallets
            foreach (WalletCurrency::cases() as $currency) {
                $this->walletRepository->create([
                    'user_id' => $user->id,
                    'currency' => $currency,
                    'balance' => 0.0,
                    'status' => \App\Enums\WalletStatus::ACTIVE,
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        });
    }

    public function login(array $credentials): ?array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
