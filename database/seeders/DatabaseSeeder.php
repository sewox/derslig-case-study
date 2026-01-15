<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Enums\UserRole;
use App\Enums\WalletCurrency;
use App\Enums\WalletStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Services\AuthService;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
        ]);
        $this->createWallets($admin);

        // Create Regular Users
        $users = User::factory(5)->create([
            'role' => UserRole::USER,
        ]);

        foreach ($users as $user) {
            $this->createWallets($user);
            // Add some balance
            $wallet = $user->wallets()->where('currency', WalletCurrency::TRY)->first();
            $wallet->update(['balance' => 10000]);
        }
    }

    protected function createWallets(User $user): void
    {
        foreach (WalletCurrency::cases() as $currency) {
            Wallet::create([
                'user_id' => $user->id,
                'currency' => $currency,
                'balance' => 0.0,
                'status' => WalletStatus::ACTIVE,
            ]);
        }
    }
}
