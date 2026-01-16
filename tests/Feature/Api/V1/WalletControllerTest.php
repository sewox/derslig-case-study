<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Enums\WalletCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $walletTry;
    protected $walletUsd;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->walletTry = $this->user->wallets()->create([
            'currency' => WalletCurrency::TRY,
            'balance' => 1000.00,
            'status' => 'active'
        ]);
        $this->walletUsd = $this->user->wallets()->create([
            'currency' => WalletCurrency::USD,
            'balance' => 500.00,
            'status' => 'active'
        ]);
    }

    public function test_index_returns_user_wallets()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/wallets');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    public function test_show_returns_single_wallet()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/wallets/' . $this->walletTry->id);

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $this->walletTry->id)
                 ->assertJsonPath('data.currency', 'TRY');
    }

    public function test_show_returns_404_for_other_users_wallet()
    {
        $otherUser = User::factory()->create();
        $otherWallet = $otherUser->wallets()->create([
            'currency' => WalletCurrency::EUR,
            'balance' => 200,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/wallets/' . $otherWallet->id);

        $response->assertStatus(404);
    }

    public function test_show_returns_404_for_invalid_wallet_id()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/wallets/invalid-uuid');

        $response->assertStatus(404);
    }

    public function test_index_requires_authentication()
    {
        $response = $this->getJson('/api/v1/wallets');

        $response->assertStatus(401);
    }
}
