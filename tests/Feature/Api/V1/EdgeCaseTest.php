<?php

namespace Tests\Feature\Api\V1;

use App\Models\Configuration;
use App\Models\User;
use App\Enums\WalletCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $wallet;

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
        $this->wallet = $this->user->wallets()->create([
            'currency' => WalletCurrency::TRY,
            'balance' => 10000.00,
            'status' => 'active'
        ]);
    }

    public function test_cannot_deposit_negative_amount()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/deposit', [
            'wallet_id' => $this->wallet->id,
            'amount' => -100.00
        ]);

        $response->assertStatus(422); // Validation error
    }

    public function test_cannot_deposit_zero_amount()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/deposit', [
            'wallet_id' => $this->wallet->id,
            'amount' => 0
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_withdraw_negative_amount()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/withdraw', [
            'wallet_id' => $this->wallet->id,
            'amount' => -50.00
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_transfer_to_nonexistent_user()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/transfer', [
            'amount' => 100.00,
            'currency' => 'TRY',
            'target_user_email' => 'nonexistent@example.com'
        ]);

        $response->assertStatus(422); // Validation: user not found
    }

    public function test_cannot_transfer_with_invalid_currency()
    {
        $receiver = User::factory()->create();
        $receiver->wallets()->create([
            'currency' => WalletCurrency::TRY,
            'balance' => 0,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/transfer', [
            'amount' => 100.00,
            'currency' => 'INVALID',
            'target_user_email' => $receiver->email
        ]);

        $response->assertStatus(422);
    }

    // Blocked wallet check is handled by FraudCheck. For unit testing, skip this complex scenario.

    public function test_exact_boundary_daily_limit()
    {
        // Set daily limit to exactly 1000
        Configuration::updateOrCreate(['key' => 'DAILY_TRANSFER_LIMIT_TRY'], ['value' => '1000.0', 'description' => 'd']);
        
        $receiver = User::factory()->create();
        $receiver->wallets()->create([
            'currency' => WalletCurrency::TRY,
            'balance' => 0,
            'status' => 'active'
        ]);

        // Transfer exactly 1000 (at boundary, should pass)
        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/transfer', [
            'amount' => 1000.00,
            'currency' => 'TRY',
            'target_user_email' => $receiver->email
        ]);

        $response->assertStatus(200);
    }

    public function test_exceeding_daily_limit_by_one()
    {
        // Set daily limit to exactly 1000
        Configuration::updateOrCreate(['key' => 'DAILY_TRANSFER_LIMIT_TRY'], ['value' => '1000.0', 'description' => 'd']);
        
        $receiver = User::factory()->create();
        $receiver->wallets()->create([
            'currency' => WalletCurrency::TRY,
            'balance' => 0,
            'status' => 'active'
        ]);

        // Transfer 1001 (exceeding by 1, should fail)
        $response = $this->actingAs($this->user)->postJson('/api/v1/transactions/transfer', [
            'amount' => 1001.00,
            'currency' => 'TRY',
            'target_user_email' => $receiver->email
        ]);

        $response->assertStatus(400);
    }

    public function test_user_logout()
    {
        // Login first
        $this->user->update(['password' => bcrypt('password')]);
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password'
        ]);
        
        $token = $loginResponse->json('data.token');

        // Logout
        $logoutResponse = $this->withToken($token)->postJson('/api/v1/auth/logout');
        $logoutResponse->assertStatus(200)
                       ->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_me_endpoint_returns_user_data()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $this->user->id)
                 ->assertJsonPath('data.email', $this->user->email);
    }
}
