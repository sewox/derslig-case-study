<?php

namespace Tests\Feature\Api\V1;

use App\Models\Configuration;
use App\Models\User;
use App\Enums\WalletCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
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

    public function test_user_can_list_own_wallets()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/wallets');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    public function test_user_can_view_single_wallet()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/wallets/' . $this->walletTry->id);

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $this->walletTry->id)
                 ->assertJsonPath('data.currency', 'TRY')
                 ->assertJsonPath('data.balance', '1000.0000');
    }

    public function test_user_cannot_view_other_users_wallet()
    {
        $otherUser = User::factory()->create();
        $otherWallet = $otherUser->wallets()->create([
            'currency' => WalletCurrency::EUR,
            'balance' => 200.00,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/wallets/' . $otherWallet->id);

        // Should return 404 (not found) or 403 (forbidden)
        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_access_wallets()
    {
        $response = $this->getJson('/api/v1/wallets');

        $response->assertStatus(401);
    }
}
