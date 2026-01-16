<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TransactionService;
use App\DTO\TransactionDTO;
use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
use App\Enums\WalletCurrency;
use Exception;

class SimulateTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:simulate-transactions {--count=10}';

    protected $description = 'Simulate random transactions between users with detailed sub-steps';

    public function handle(TransactionService $transactionService)
    {
        $count = (int) $this->option('count');
        $this->info("\n🚀 Starting simulation of {$count} transactions with detailed tracing...\n");

        $users = User::with('wallets')->get();

        if ($users->count() < 2) {
            $this->error('Not enough users to simulate transactions.');
            return;
        }

        $successCount = 0;
        $failCount = 0;

        for ($i = 1; $i <= $count; $i++) {
            $this->line("<fg=blue;options=bold># Iteration {$i}/{$count}</>");
            
            try {
                // 1. Pick Sender
                $sender = $users->random();
                $this->line("  <fg=gray>├─</> Sender: <fg=white>{$sender->name}</> ({$sender->email})");
                
                // 2. Pick Sender's Wallet
                $sourceWallet = $sender->wallets->random();
                $sourceWallet->refresh(); // Get latest balance
                $this->line("  <fg=gray>├─</> Source Wallet: <fg=yellow>{$sourceWallet->currency->value}</> (Balance: {$sourceWallet->balance}, ID: " . substr($sourceWallet->id, 0, 8) . "...)");
                
                // 3. Pick Receiver
                $receiver = $users->where('id', '!=', $sender->id)->random();
                $this->line("  <fg=gray>├─</> Receiver: <fg=white>{$receiver->name}</> ({$receiver->email})");

                // 4. Find matching currency wallet for receiver
                $targetWallet = $receiver->wallets->where('currency', $sourceWallet->currency)->first();

                if (!$targetWallet) {
                    $this->line("  <fg=gray>└─</> <fg=yellow>SKIPPED</>: Receiver has no matching {$sourceWallet->currency->value} wallet.");
                    $failCount++;
                    $this->newLine();
                    continue;
                }
                $this->line("  <fg=gray>├─</> Target Wallet: <fg=yellow>{$targetWallet->currency->value}</> (Balance: {$targetWallet->balance}, ID: " . substr($targetWallet->id, 0, 8) . "...)");

                // 5. Determine Amount
                $amount = rand(500, 20000) / 100; // 5.00 to 200.00
                $this->line("  <fg=gray>├─</> Attempting Transfer: <fg=cyan>{$amount} {$sourceWallet->currency->value}</>");

                // Auto-refill logic for simulation variety
                if ($sourceWallet->balance < ($amount + 10)) {
                    $refillAmount = 1000.0;
                    $this->line("  <fg=gray>├─</> <fg=magenta>AUTO-REFILL</>: Balance too low. Depositing <fg=magenta>{$refillAmount}</>...");
                    $this->depositFunds($sender, $sourceWallet, $transactionService);
                    $sourceWallet->refresh();
                    $this->line("  <fg=gray>├─</> New Balance: <fg=white>{$sourceWallet->balance}</>");
                }

                // 6. Execute Transfer
                $dto = new TransactionDTO(
                    user: $sender,
                    sourceWallet: $sourceWallet,
                    targetWallet: $targetWallet,
                    amount: (float)$amount,
                    type: TransactionType::TRANSFER,
                    description: "Simulated transfer #{$i}",
                    ipAddress: '127.0.0.1'
                );

                $transaction = $transactionService->processTransaction($dto);

                $this->line("  <fg=gray>└─</> <fg=green;options=bold>SUCCESS</>: Transaction Created. ID: " . substr($transaction->id, 0, 8) . "... Fee: {$transaction->fee}");
                $successCount++;

            } catch (Exception $e) {
                $this->line("  <fg=gray>└─</> <fg=red;options=bold>FAILED</>: " . $e->getMessage());
                $failCount++;
            }
            
            $this->newLine();
        }

        $this->info("--- Simulation Summary ---");
        $this->table(
            ['Result', 'Count'],
            [
                ['Success', "<fg=green>{$successCount}</>"],
                ['Failed/Skipped', "<fg=red>{$failCount}</>"],
                ['Total', ($successCount + $failCount)]
            ]
        );
    }

    private function depositFunds(User $user, Wallet $wallet, TransactionService $service)
    {
        $dto = new TransactionDTO(
            user: $user,
            sourceWallet: null,
            targetWallet: $wallet,
            amount: 1000.0,
            type: TransactionType::DEPOSIT,
            status: TransactionStatus::COMPLETED,
            description: "Simulation auto-refill"
        );
        $service->processTransaction($dto);
    }
}
