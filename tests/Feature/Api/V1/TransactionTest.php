<?php

namespace Tests\Feature\Api\V1;

use App\Models\Configuration;
use App\Models\User;
use App\Enums\WalletCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected $sender;
    protected $receiver;
    protected $senderWallet;
    protected $receiverWallet;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed Configurations
        Configuration::updateOrCreate(['key' => 'FEE_LOW_FIXED'], ['value' => '2.0', 'description' => 'desc']);
        Configuration::updateOrCreate(['key' => 'FEE_MEDIUM_RATE'], ['value' => '0.005', 'description' => 'desc']);
        Configuration::updateOrCreate(['key' => 'FEE_HIGH_BASE_FEE'], ['value' => '2.0', 'description' => 'desc']);
        Configuration::updateOrCreate(['key' => 'FEE_HIGH_RATE'], ['value' => '0.003', 'description' => 'desc']);
        Configuration::updateOrCreate(['key' => 'FEE_THRESHOLD_LOW'], ['value' => '1000.0', 'description' => 'desc']);
        Configuration::updateOrCreate(['key' => 'FEE_THRESHOLD_MEDIUM'], ['value' => '10000.0', 'description' => 'desc']);
        Configuration::updateOrCreate(['key' => 'DAILY_TRANSFER_LIMIT_TRY'], ['value' => '50000.0', 'description' => 'desc']);
        Configuration::updateOrCreate(['key' => 'FRAUD_CHECK_VELOCITY_LIMIT'], ['value' => '10', 'description' => 'desc']); 
        Configuration::updateOrCreate(['key' => 'FRAUD_CHECK_NEW_ACCOUNT_DAYS'], ['value' => '0', 'description' => 'desc']); 

        // Create Users
        $this->sender = User::factory()->create();
        $this->receiver = User::factory()->create();

        // Create TRY Wallets safely
        $this->senderWallet = $this->sender->wallets()->firstOrCreate(
            ['currency' => WalletCurrency::TRY],
            ['balance' => 0.00, 'status' => 'active']
        );

        $this->receiverWallet = $this->receiver->wallets()->firstOrCreate(
            ['currency' => WalletCurrency::TRY],
            ['balance' => 0.00, 'status' => 'active']
        );
    }

    public function test_user_can_deposit_funds()
    {
        $response = $this->actingAs($this->sender)->postJson('/api/v1/transactions/deposit', [
            'wallet_id' => $this->senderWallet->id,
            'amount' => 20000.00
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('wallets', [
            'id' => $this->senderWallet->id,
            'balance' => 20000.00
        ]);
    }

    public function test_user_can_transfer_funds_with_fee()
    {
        // 1. Check Deposit First
        $this->senderWallet->update(['balance' => 20000.00]);

        // 2. Transfer 15,000 TRY (High Tier)
        $amount = 15000.00;
        // Fee Calculation: 15,000 > 10,000 (Medium Threshold)
        // High Strategy: Base(2.0) + (15000 - 1000) * 0.003
        // 2.0 + 14000 * 0.003 = 2.0 + 42.0 = 44.0
        $fee = 44.0;
        
        $response = $this->actingAs($this->sender)->postJson('/api/v1/transactions/transfer', [
            'amount' => $amount,
            'currency' => 'TRY',
            'target_user_email' => $this->receiver->email
        ]);

        $response->assertStatus(200);

        // Assert Sender Balance: 20000 - 15000 - 44 = 4956
        $this->assertDatabaseHas('wallets', [
            'id' => $this->senderWallet->id,
            'balance' => 4956.00
        ]);

        // Assert Receiver Balance: 0 + 15000 = 15000
        $this->assertDatabaseHas('wallets', [
            'id' => $this->receiverWallet->id,
            'balance' => 15000.00
        ]);
        
        // Assert Transaction Records
        // Assert Transaction Record (One record for transfer, linking source and target)
        // Note: The system architecture creates ONE transaction record for a transfer, 
        // linking source_wallet_id and target_wallet_id.
        // It does NOT create two separate records (debit/credit) in the transactions table,
        // although the updated logic in TransactionService updates both balances.
        
        $this->assertDatabaseHas('transactions', [
            'source_wallet_id' => $this->senderWallet->id,
            'target_wallet_id' => $this->receiverWallet->id,
            'type' => 'transfer',
            'amount' => 15000.00,
            'fee' => 44.00,
            'currency' => 'TRY'
        ]);
    }

    public function test_cannot_withdraw_insufficient_funds()
    {
        $this->senderWallet->update(['balance' => 100.00]);

        $response = $this->actingAs($this->sender)->postJson('/api/v1/transactions/withdraw', [
            'wallet_id' => $this->senderWallet->id,
            'amount' => 500.00
        ]);

        $response->assertStatus(400); // Bad Request
    }

    public function test_cannot_transfer_to_self()
    {
        $response = $this->actingAs($this->sender)->postJson('/api/v1/transactions/transfer', [
            'amount' => 50.00,
            'currency' => 'TRY',
            'target_user_email' => $this->sender->email
        ]);

        $response->assertStatus(400);
    }
}
