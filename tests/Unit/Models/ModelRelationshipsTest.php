<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\SuspiciousActivity;
use App\Enums\WalletCurrency;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_wallets()
    {
        $user = User::factory()->create();
        
        $user->wallets()->create(['currency' => WalletCurrency::TRY, 'balance' => 100, 'status' => 'active']);
        $user->wallets()->create(['currency' => WalletCurrency::USD, 'balance' => 200, 'status' => 'active']);

        $this->assertCount(2, $user->wallets);
        $this->assertInstanceOf(Wallet::class, $user->wallets->first());
    }

    public function test_user_has_many_suspicious_activities()
    {
        $user = User::factory()->create();
        
        SuspiciousActivity::create([
            'user_id' => $user->id,
            'rule_type' => 'test_rule',
            'severity' => 'high',
            'status' => 'pending',
            'risk_score' => 80
        ]);

        $this->assertCount(1, $user->suspiciousActivities);
        $this->assertInstanceOf(SuspiciousActivity::class, $user->suspiciousActivities->first());
    }

    public function test_user_is_admin_method()
    {
        $admin = User::factory()->create(['role' => \App\Enums\UserRole::ADMIN]);
        $regularUser = User::factory()->create(['role' => \App\Enums\UserRole::USER]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($regularUser->isAdmin());
    }

    public function test_wallet_belongs_to_user()
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create([
            'currency' => WalletCurrency::TRY,
            'balance' => 1000,
            'status' => 'active'
        ]);

        $this->assertInstanceOf(User::class, $wallet->user);
        $this->assertEquals($user->id, $wallet->user->id);
    }

    public function test_transaction_belongs_to_source_and_target_wallets()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        
        $sourceWallet = $sender->wallets()->create(['currency' => WalletCurrency::TRY, 'balance' => 1000, 'status' => 'active']);
        $targetWallet = $receiver->wallets()->create(['currency' => WalletCurrency::TRY, 'balance' => 0, 'status' => 'active']);

        $transaction = Transaction::create([
            'source_wallet_id' => $sourceWallet->id,
            'target_wallet_id' => $targetWallet->id,
            'amount' => 500,
            'fee' => 2,
            'currency' => WalletCurrency::TRY,
            'type' => TransactionType::TRANSFER,
            'status' => TransactionStatus::COMPLETED,
        ]);

        $this->assertInstanceOf(Wallet::class, $transaction->sourceWallet);
        $this->assertInstanceOf(Wallet::class, $transaction->targetWallet);
        $this->assertEquals($sourceWallet->id, $transaction->sourceWallet->id);
        $this->assertEquals($targetWallet->id, $transaction->targetWallet->id);
    }

    public function test_transaction_casts_enums_correctly()
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create(['currency' => WalletCurrency::TRY, 'balance' => 1000, 'status' => 'active']);

        $transaction = Transaction::create([
            'target_wallet_id' => $wallet->id,
            'amount' => 100,
            'fee' => 0,
            'currency' => WalletCurrency::TRY,
            'type' => TransactionType::DEPOSIT,
            'status' => TransactionStatus::COMPLETED,
        ]);

        $this->assertInstanceOf(TransactionType::class, $transaction->type);
        $this->assertInstanceOf(TransactionStatus::class, $transaction->status);
        $this->assertInstanceOf(WalletCurrency::class, $transaction->currency);
    }

    public function test_wallet_uses_soft_deletes()
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create(['currency' => WalletCurrency::TRY, 'balance' => 1000, 'status' => 'active']);

        $walletId = $wallet->id;
        $wallet->delete();

        $this->assertSoftDeleted('wallets', ['id' => $walletId]);
        $this->assertNull(Wallet::find($walletId));
        $this->assertNotNull(Wallet::withTrashed()->find($walletId));
    }

    public function test_transaction_uses_soft_deletes()
    {
        $user = User::factory()->create();
        $wallet = $user->wallets()->create(['currency' => WalletCurrency::TRY, 'balance' => 1000, 'status' => 'active']);

        $transaction = Transaction::create([
            'target_wallet_id' => $wallet->id,
            'amount' => 100,
            'fee' => 0,
            'currency' => WalletCurrency::TRY,
            'type' => TransactionType::DEPOSIT,
            'status' => TransactionStatus::COMPLETED,
        ]);

        $txnId = $transaction->id;
        $transaction->delete();

        $this->assertSoftDeleted('transactions', ['id' => $txnId]);
    }
}
