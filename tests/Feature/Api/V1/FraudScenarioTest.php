<?php

namespace Tests\Feature\Api\V1;

use App\Models\Configuration;
use App\Models\User;
use App\Enums\WalletCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FraudScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected $sender;
    protected $receiver;
    protected $senderWallet;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Force sync queue driver to ensure listeners run immediately
        config(['queue.default' => 'sync']);
        
        // Setup Basic Configs
        Configuration::updateOrCreate(['key' => 'FEE_LOW_FIXED'], ['value' => '0']); // Simplify fees
        Configuration::updateOrCreate(['key' => 'FEE_MEDIUM_RATE'], ['value' => '0']);
        Configuration::updateOrCreate(['key' => 'FEE_HIGH_BASE_FEE'], ['value' => '0']);
        Configuration::updateOrCreate(['key' => 'FEE_HIGH_RATE'], ['value' => '0']);
        Configuration::updateOrCreate(['key' => 'FEE_THRESHOLD_LOW'], ['value' => '1000.0']);
        Configuration::updateOrCreate(['key' => 'FEE_THRESHOLD_MEDIUM'], ['value' => '10000.0']);
        Configuration::updateOrCreate(['key' => 'DAILY_TRANSFER_LIMIT_TRY'], ['value' => '500000.0']);

        // Fraud Specific Configs
        Configuration::updateOrCreate(['key' => 'FRAUD_CHECK_VELOCITY_LIMIT'], ['value' => '3']); // Block on 4th attempt
        Configuration::updateOrCreate(['key' => 'FRAUD_CHECK_VELOCITY_WINDOW_MINUTES'], ['value' => '10']);
        
        // Night Configs
        Configuration::updateOrCreate(['key' => 'FRAUD_CHECK_NIGHT_START_HOUR'], ['value' => '2']);
        Configuration::updateOrCreate(['key' => 'FRAUD_CHECK_NIGHT_END_HOUR'], ['value' => '6']);
        Configuration::updateOrCreate(['key' => 'FRAUD_CHECK_NIGHT_AMOUNT_LIMIT'], ['value' => '1000.0']);

        // New Account Configs
        Configuration::updateOrCreate(['key' => 'FRAUD_CHECK_NEW_ACCOUNT_DAYS'], ['value' => '0']); // Enabled but 0 days for standard test, overridden in specific test
        Configuration::updateOrCreate(['key' => 'FRAUD_CHECK_NEW_ACCOUNT_AMOUNT_LIMIT'], ['value' => '5000.0']);


        $this->sender = User::factory()->create();
        $this->receiver = User::factory()->create();

        $this->senderWallet = $this->sender->wallets()->firstOrCreate(
            ['currency' => WalletCurrency::TRY],
            ['balance' => 50000.00, 'status' => 'active']
        );
        
        // Clear previous transactions
        // Use delete() instead of truncate() to respect FK constraints
        DB::table('suspicious_activities')->delete();
        DB::table('transactions')->delete();
    }

    public function test_velocity_check_blocks_excessive_transactions()
    {
        // Velocity check counts DISTINCT recipients. 
        // We need to create multiple receivers to trigger the velocity limit.
        // Limit is 3 distinct users. So after transferring to 3 different users,
        // the 4th transfer to a NEW user should be blocked.

        $receivers = [];
        for ($i = 0; $i < 4; $i++) {
            $receiver = User::factory()->create();
            $receiver->wallets()->create([
                'currency' => WalletCurrency::TRY,
                'balance' => 0.00,
                'status' => 'active'
            ]);
            $receivers[] = $receiver;
        }

        // 1. First 3 transactions to DIFFERENT users should pass (Limit is 3)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->actingAs($this->sender)->postJson('/api/v1/transactions/transfer', [
                'amount' => 100.00,
                'currency' => 'TRY',
                'target_user_email' => $receivers[$i]->email
            ]);
            $response->assertStatus(200);
        }

        // 2. Fourth transaction to A NEW (4th) user should fail and trigger fraud check
        $response = $this->actingAs($this->sender)->postJson('/api/v1/transactions/transfer', [
            'amount' => 100.00,
            'currency' => 'TRY',
            'target_user_email' => $receivers[3]->email
        ]);

        $response->assertStatus(400)
                 ->assertJson(['message' => __('messages.transaction.fraud.velocity_limit_exceeded')]);

        // 3. Verify Suspicious Activity Record
        $this->assertDatabaseHas('suspicious_activities', [
            'user_id' => $this->sender->id,
            'rule_type' => 'velocity_limit_exceeded',
            'status' => 'pending'
        ]);
        
        // 4. Verify Wallet is Blocked
        $this->assertDatabaseHas('wallets', [
            'id' => $this->senderWallet->id,
            'status' => 'blocked',
            'blocked_reason' => 'Fraud Detection: velocity_limit_exceeded'
        ]);
    }

    public function test_night_transaction_limit()
    {
        // Set Mock Time to 03:00 AM
        Carbon::setTestNow(Carbon::createFromTime(3, 0, 0));

        // Ensure receiver wallet
        $this->receiver->wallets()->firstOrCreate(
             ['currency' => WalletCurrency::TRY],
             ['balance' => 0.00, 'status' => 'active']
        );

        // Try to send 2000 TRY (Limit is 1000)
        $response = $this->actingAs($this->sender)->postJson('/api/v1/transactions/transfer', [
            'amount' => 2000.00,
            'currency' => 'TRY',
            'target_user_email' => $this->receiver->email
        ]);
        
        $response->assertStatus(400)
                 ->assertJson(['message' => __('messages.transaction.fraud.night_transaction_limit')]);

        $this->assertDatabaseHas('suspicious_activities', [
            'user_id' => $this->sender->id,
            'rule_type' => 'night_transaction_limit'
        ]);

        Carbon::setTestNow(); // Reset time
    }

    public function test_new_account_high_amount_limit()
    {
        // Setup Sender as Brand New User
        $newUser = User::factory()->create(['created_at' => now()]);
        Configuration::updateOrCreate(['key' => 'FRAUD_CHECK_NEW_ACCOUNT_DAYS'], ['value' => '7']);
        
        $newWallet = $newUser->wallets()->create([
            'currency' => WalletCurrency::TRY,
            'balance' => 10000.00,
            'status' => 'active'
        ]);

        // Ensure receiver has a wallet
        $this->receiver->wallets()->firstOrCreate(
             ['currency' => WalletCurrency::TRY],
             ['balance' => 0.00, 'status' => 'active']
        );

        // Try to send 6000 TRY (Limit 5000)
        $response = $this->actingAs($newUser)->postJson('/api/v1/transactions/transfer', [
            'amount' => 6000.00,
            'currency' => 'TRY',
            'target_user_email' => $this->receiver->email
        ]);

        $response->assertStatus(400)
                 ->assertJson(['message' => __('messages.transaction.fraud.new_account_high_amount')]);

        $this->assertDatabaseHas('suspicious_activities', [
            'user_id' => $newUser->id,
            'rule_type' => 'new_account_high_amount'
        ]);
    }
}
