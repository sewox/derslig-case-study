<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Enums\WalletCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token'
                ]
            ]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);

        // Check if Wallets are created
        $user = User::where('email', 'test@example.com')->first();
        $this->assertCount(3, $user->wallets); // TRY, USD, EUR
        $this->assertTrue($user->wallets()->where('currency', WalletCurrency::TRY)->exists());
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user',
                    'token'
                ]
            ]);
    }

    public function test_login_revokes_previous_tokens()
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        // Login 1
        $response1 = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token1 = $response1->json('data.token');

        // Login 2
        $response2 = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token2 = $response2->json('data.token');

        // Old token should be invalid (assuming we check DB, but feature test can try to use it)
        $this->assertNotEquals($token1, $token2);
        
        // Try accessing protected route with Token 1
        $response = $this->withToken($token1)->getJson('/api/v1/auth/me');
        $response->assertStatus(401);

        // Try accessing protected route with Token 2
        $response = $this->withToken($token2)->getJson('/api/v1/auth/me');
        $response->assertStatus(200);
    }

    public function test_locale_middleware_returns_translated_response()
    {
        // Default (English)
        User::factory()->create(['email' => 'en@test.com', 'password' => bcrypt('password')]);
        
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'en@test.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid credentials']);

        // Turkish Request
        $responseTr = $this->postJson('/api/v1/auth/login', [
            'email' => 'en@test.com',
            'password' => 'wrong-pass',
        ], ['Accept-Language' => 'tr']);

        $responseTr->assertStatus(401)
                   ->assertJson(['message' => 'Geçersiz kimlik bilgileri']); // Assuming this is the TR translation
    }
}
