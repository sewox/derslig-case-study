<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin-user {name} {email} {password}';

    protected $description = 'Create a new admin user';

    public function handle(\App\Repository\UserRepository $userRepository, \App\Repository\WalletRepository $walletRepository)
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');

        if (\App\Models\User::where('email', $email)->exists()) {
            $this->error('User with this email already exists.');
            return;
        }

        $user = \App\Models\User::create([
            'name' => $name,
            'email' => $email,
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        // Create default wallets
        foreach (\App\Enums\WalletCurrency::cases() as $currency) {
            $walletRepository->create([
                'user_id' => $user->id,
                'currency' => $currency,
                'balance' => 0.0,
                'status' => \App\Enums\WalletStatus::ACTIVE,
            ]);
        }

        $this->info("Admin user {$name} created successfully.");
    }
}
