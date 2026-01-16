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
        // Seed Configuration
        \App\Models\Configuration::firstOrCreate(
            ['key' => 'DAILY_TRANSFER_LIMIT_TRY'],
            ['value' => '50000', 'description' => 'Daily transfer limit in TRY']
        );
        \App\Models\Configuration::firstOrCreate(
            ['key' => 'DAILY_WITHDRAW_LIMIT_EUR'],
            ['value' => '50000', 'description' => 'Daily withdraw limit in EUR']
        );
        \App\Models\Configuration::firstOrCreate(
            ['key' => 'DAILY_WITHDRAW_LIMIT_USD'],
            ['value' => '50000', 'description' => 'Daily withdraw limit in USD']
        );

        // Fraud Check Configurations
        \App\Models\Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_VELOCITY_WINDOW_MINUTES'], ['value' => '60', 'description' => 'Fraud check velocity window in minutes']);
        \App\Models\Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_VELOCITY_LIMIT'], ['value' => '4', 'description' => 'Fraud check velocity transfer limit']);
        \App\Models\Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_NIGHT_START_HOUR'], ['value' => '2', 'description' => 'Fraud check night start hour']);
        \App\Models\Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_NIGHT_END_HOUR'], ['value' => '6', 'description' => 'Fraud check night end hour']);
        \App\Models\Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_NIGHT_AMOUNT_LIMIT'], ['value' => '5000', 'description' => 'Fraud check night amount limit']);
        \App\Models\Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_NEW_ACCOUNT_DAYS'], ['value' => '7', 'description' => 'Fraud check new account days']);
        \App\Models\Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_NEW_ACCOUNT_AMOUNT_LIMIT'], ['value' => '10000', 'description' => 'Fraud check new account amount limit']);
        \App\Models\Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_IP_WINDOW_MINUTES'], ['value' => '1440', 'description' => 'Fraud check IP window in minutes']);

        // Fee Configurations
        \App\Models\Configuration::firstOrCreate(['key' => 'FEE_THRESHOLD_LOW'], ['value' => '1000', 'description' => 'Fee threshold low amount']);
        \App\Models\Configuration::firstOrCreate(['key' => 'FEE_THRESHOLD_MEDIUM'], ['value' => '10000', 'description' => 'Fee threshold medium amount']);
        \App\Models\Configuration::firstOrCreate(['key' => 'FEE_LOW_FIXED'], ['value' => '2.0', 'description' => 'Fee low fixed amount']);
        \App\Models\Configuration::firstOrCreate(['key' => 'FEE_MEDIUM_RATE'], ['value' => '0.005', 'description' => 'Fee medium rate']);
        \App\Models\Configuration::firstOrCreate(['key' => 'FEE_HIGH_BASE_FEE'], ['value' => '2.0', 'description' => 'Fee high base fee']);
        \App\Models\Configuration::firstOrCreate(['key' => 'FEE_HIGH_RATE'], ['value' => '0.003', 'description' => 'Fee high rate']);


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
