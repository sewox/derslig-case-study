<?php

namespace Tests\Feature\Api\V1;

use App\Models\Configuration;
use App\Models\User;
use App\Enums\WalletCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $userWalletTry;
    protected $receiver;
    protected $receiverWalletTry;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup configs
        Configuration::updateOrCreate(['key' => 'FEE_LOW_FIXED'], ['value' => '2.0', 'description' => 'd']);
        Configuration::updateOrCreate(['key' => 'FEE_MEDIUM_RATE'], ['value' => '0.005', 'description' => 'd']);
        Configuration::updateOrCreate(['key' => 'FEE_HIGH_BASE_FEE'], ['value' => '2.0', 'description' => 'd']);
        Configuration::updateOrCreate(['key' => 'FEE_HIGH_RATE'], ['value' => '0.003', 'description' => 'd']);
        Configuration::updateOrCreate(['key' => 'FEE_THRESHOLD_LOW'], ['value' => '1000.0', 'description' => 'd']);
        Configuration::updateOrCreate(['key' => 'FEE_THRESHOLD_MEDIUM'], ['value' => '10000.0', 'description' => 'd']);
        Configuration::updateOrCreate(['key' => 'DAILY_TRANSFER_LIMIT_TRY'], ['value' => '50000.0', 'description' => 'd']);
        Configuration::updateOrCreate(['key' => 'FRAUD_CHECK_VELOCITY_LIMIT'], ['value' => '10', 'description' => 'd']);
        Configuration::updateOrCreate(['key' => 'FRAUD_CHECK_NEW_ACCOUNT_DAYS'], ['value' => '0', 'description' => 'd']);

        $this->user = User::factory()->create();
        $this->userWalletTry = $this->user->wallets()->create([
            'currency' => WalletCurrency::TRY,
            'balance' => 10000.00,
            'status' => 'active'
        ]);

        $this->receiver = User::factory()->create();
        $this->receiverWalletTry = $this->receiver->wallets()->create([
            'currency' => WalletCurrency::TRY,
            'balance' => 0.00,
            'status' => 'active'
        ]);
    }

    public function test_deposit_endpoint()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/deposit', [
            'wallet_id' => $this->userWalletTry->id,
            'amount' => 500.00
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['message', 'data']);
        
        $this->assertDatabaseHas('wallets', [
            'id' => $this->userWalletTry->id,
            'balance' => 10500.00
        ]);
    }

    public function test_withdraw_endpoint()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/withdraw', [
            'wallet_id' => $this->userWalletTry->id,
            'amount' => 500.00
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseHas('wallets', [
            'id' => $this->userWalletTry->id,
            'balance' => 9500.00 // 10000 - 500
        ]);
    }

    public function test_transfer_endpoint_with_low_fee()
    {
        // Transfer 500 TRY (Low tier, fixed fee 2.0)
        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/transfer', [
            'amount' => 500.00,
            'currency' => 'TRY',
            'target_user_email' => $this->receiver->email
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['message', 'data']);

        // Sender: 10000 - 500 - 2 = 9498
        $this->assertDatabaseHas('wallets', [
            'id' => $this->userWalletTry->id,
            'balance' => 9498.00
        ]);

        // Receiver: 0 + 500 = 500
        $this->assertDatabaseHas('wallets', [
            'id' => $this->receiverWalletTry->id,
            'balance' => 500.00
        ]);
    }

    public function test_transfer_endpoint_with_medium_fee()
    {
        // Transfer 5000 TRY (Medium tier, 0.5% = 25)
        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/transfer', [
            'amount' => 5000.00,
            'currency' => 'TRY',
            'target_user_email' => $this->receiver->email
        ]);

        $response->assertStatus(200);

        // Sender: 10000 - 5000 - 25 = 4975
        $this->assertDatabaseHas('wallets', [
            'id' => $this->userWalletTry->id,
            'balance' => 4975.00
        ]);
    }

    public function test_transfer_requires_authentication()
    {
        $response = $this->postJson('/api/v1/transactions/transfer', [
            'amount' => 100.00,
            'currency' => 'TRY',
            'target_user_email' => $this->receiver->email
        ]);

        $response->assertStatus(401);
    }

    public function test_deposit_requires_valid_wallet_id()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/deposit', [
            'wallet_id' => 'invalid-uuid',
            'amount' => 100.00
        ]);

        $response->assertStatus(422);
    }

    public function test_transfer_validates_amount_is_positive()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/transfer', [
            'amount' => -100.00,
            'currency' => 'TRY',
            'target_user_email' => $this->receiver->email
        ]);

        $response->assertStatus(422);
    }

    public function test_transfer_validates_currency()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/transfer', [
            'amount' => 100.00,
            'currency' => 'INVALID',
            'target_user_email' => $this->receiver->email
        ]);

        $response->assertStatus(422);
    }
}
