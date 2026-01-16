<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DailyReconciliation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-reconciliation';

    protected $description = 'Generate daily reconciliation report for transactions';

    public function handle()
    {
        $date = now()->toDateString();
        $this->info("Generating Reconciliation Report for: {$date}");

        $stats = \App\Models\Transaction::query()
            ->whereDate('created_at', $date)
            ->selectRaw('type, currency, SUM(amount) as total_amount, SUM(fee) as total_fee, COUNT(*) as count')
            ->groupBy('type', 'currency')
            ->get();

        if ($stats->isEmpty()) {
            $this->info('No transactions found for today.');
            return;
        }

        $headers = ['Type', 'Currency', 'Total Amount', 'Total Fee', 'Count'];
        $rows = $stats->map(function ($stat) {
            return [
                $stat->type->value,
                $stat->currency->value,
                number_format((float)$stat->total_amount, 2),
                number_format((float)$stat->total_fee, 2),
                $stat->count,
            ];
        })->toArray();

        $this->table($headers, $rows);
        
        $this->info('Report generated.');
    }
}
