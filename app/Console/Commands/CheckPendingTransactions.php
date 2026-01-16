<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckPendingTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:check-pending';

    protected $description = 'Check for transactions pending review older than 24 hours';

    public function handle()
    {
        $this->info('Checking for long-pending transactions...');

        $transactions = \App\Models\Transaction::query()
            ->where('status', \App\Enums\TransactionStatus::PENDING_REVIEW)
            ->where('created_at', '<', now()->subHours(24))
            ->with(['sourceWallet.user'])
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('No long-pending transactions found.');
            return;
        }

        $headers = ['ID', 'User', 'Type', 'Amount', 'Currency', 'Created At'];
        $rows = $transactions->map(function ($tx) {
            return [
                $tx->id,
                $tx->sourceWallet?->user?->email ?? 'N/A',
                $tx->type->value,
                $tx->amount,
                $tx->currency->value,
                $tx->created_at->toDateTimeString(),
            ];
        })->toArray();

        $this->error("Found " . count($rows) . " transactions pending review for >24 hours!");
        $this->table($headers, $rows);
        
        // Potential extension: Send alert to admin via notification
        // \Illuminate\Support\Facades\Notification::send(...)
    }
}
