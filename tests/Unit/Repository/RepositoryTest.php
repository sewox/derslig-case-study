<?php

namespace Tests\Unit\Repository;

use App\Models\User;
use App\Repository\UserRepository;
use App\Repository\WalletRepository;
use App\Models\Wallet;
use App\Enums\WalletCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_repository_find_by_email()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $repo = app(UserRepository::class);

        $found = $repo->findByEmail('test@example.com');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_user_repository_find_by_email_returns_null_for_nonexistent()
    {
        $repo = app(UserRepository::class);

        $found = $repo->findByEmail('nonexistent@example.com');

        $this->assertNull($found);
    }

    public function test_wallet_repository_get_by_user_id()
    {
        $user = User::factory()->create();
        $wallet1 = $user->wallets()->create([
            'currency' => WalletCurrency::TRY,
            'balance' => 1000,
            'status' => 'active'
        ]);
        $wallet2 = $user->wallets()->create([
            'currency' => WalletCurrency::USD,
            'balance' => 500,
            'status' => 'active'
        ]);

        $repo = app(WalletRepository::class);
        $wallets = $repo->getWalletsByUserId($user->id);

        $this->assertCount(2, $wallets);
    }

    public function test_base_repository_all()
    {
        User::factory()->count(5)->create();
        $repo = app(UserRepository::class);

        $all = $repo->getAll();

        $this->assertCount(5, $all);
    }

    public function test_base_repository_find()
    {
        $user = User::factory()->create();
        $repo = app(UserRepository::class);

        $found = $repo->get($user->id);

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function test_base_repository_create()
    {
        $repo = app(UserRepository::class);

        $user = $repo->create([
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => bcrypt('password')
        ]);

        $this->assertNotNull($user);
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_base_repository_update()
    {
        $user = User::factory()->create(['name' => 'Original Name']);
        $repo = app(UserRepository::class);

        $updated = $repo->update($user->id, ['name' => 'Updated Name']);

        $this->assertTrue($updated);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }

    public function test_base_repository_delete_returns_false_for_nonexistent()
    {
        $repo = app(UserRepository::class);
        
        // Try to delete a non-existent record
        $deleted = $repo->delete('nonexistent-id');

        $this->assertFalse($deleted);
    }
}
