<?php

namespace Tests\Unit\Services\Transaction\Pipes;

use App\DTO\TransactionDTO;
use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\Configuration;
use App\Models\User;
use App\Services\Transaction\Pipes\CheckDailyLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckDailyLimitTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set daily limit config
        Configuration::updateOrCreate(
            ['key' => 'DAILY_TRANSFER_LIMIT_TRY'],
            ['value' => '5000.0', 'description' => 'Daily TRY limit']
        );

        $this->user = User::factory()->create();
        $this->wallet = $this->user->wallets()->create([
            'currency' => WalletCurrency::TRY,
            'balance' => 10000.00,
            'status' => 'active'
        ]);
    }

    public function test_allows_transfer_within_daily_limit()
    {
        $pipe = app(CheckDailyLimit::class);

        $dto = new TransactionDTO(
            user: $this->user,
            sourceWallet: $this->wallet,
            targetWallet: null,
            amount: 2000.00,
            type: TransactionType::TRANSFER
        );

        $result = $pipe->handle($dto, fn($dto) => $dto);

        $this->assertInstanceOf(TransactionDTO::class, $result);
    }

    public function test_blocks_transfer_exceeding_daily_limit()
    {
        $this->expectException(\Exception::class);
        // The pipe throws 'transaction_exceeds_limit' for single transaction > limit
        $this->expectExceptionMessage(__('messages.transaction.transaction_exceeds_limit'));

        $pipe = app(CheckDailyLimit::class);

        $dto = new TransactionDTO(
            user: $this->user,
            sourceWallet: $this->wallet,
            targetWallet: null,
            amount: 6000.00, // 6000 > 5000 limit
            type: TransactionType::TRANSFER
        );

        $pipe->handle($dto, fn($dto) => $dto);
    }

    public function test_allows_deposit_regardless_of_daily_limit()
    {
        $pipe = app(CheckDailyLimit::class);

        // Deposits should not be subject to daily transfer limit
        $dto = new TransactionDTO(
            user: $this->user,
            sourceWallet: null,
            targetWallet: $this->wallet,
            amount: 50000.00,
            type: TransactionType::DEPOSIT
        );

        $result = $pipe->handle($dto, fn($dto) => $dto);

        $this->assertInstanceOf(TransactionDTO::class, $result);
    }
}
