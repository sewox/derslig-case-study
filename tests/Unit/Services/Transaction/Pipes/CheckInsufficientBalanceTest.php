<?php

namespace Tests\Unit\Services\Transaction\Pipes;

use App\DTO\TransactionDTO;
use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Transaction\Pipes\CheckInsufficientBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckInsufficientBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $wallet;
    protected $pipe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->wallet = $this->user->wallets()->create([
            'currency' => WalletCurrency::TRY,
            'balance' => 1000.00,
            'status' => 'active'
        ]);
        $this->pipe = new CheckInsufficientBalance();
    }

    public function test_allows_transaction_with_sufficient_balance()
    {
        $dto = new TransactionDTO(
            user: $this->user,
            sourceWallet: $this->wallet,
            targetWallet: null,
            amount: 500.00,
            type: TransactionType::WITHDRAW
        );

        $result = $this->pipe->handle($dto, fn($dto) => $dto);

        $this->assertInstanceOf(TransactionDTO::class, $result);
        $this->assertEquals(500.00, $result->amount);
    }

    public function test_blocks_transaction_with_insufficient_balance()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('messages.transaction.insufficient_balance'));

        $dto = new TransactionDTO(
            user: $this->user,
            sourceWallet: $this->wallet,
            targetWallet: null,
            amount: 1500.00,
            type: TransactionType::WITHDRAW
        );

        $this->pipe->handle($dto, fn($dto) => $dto);
    }

    public function test_allows_deposit_regardless_of_balance()
    {
        // Deposit should always pass balance check (no source deduction)
        $dto = new TransactionDTO(
            user: $this->user,
            sourceWallet: null,
            targetWallet: $this->wallet,
            amount: 5000.00,
            type: TransactionType::DEPOSIT
        );

        $result = $this->pipe->handle($dto, fn($dto) => $dto);

        $this->assertInstanceOf(TransactionDTO::class, $result);
    }
}
