<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshStatsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stats:refresh-cache';

    protected $description = 'Refresh statistics cache for admin dashboard';

    public function handle()
    {
        $this->info('Refreshing statistics cache...');

        // Clear existing cache keys (Theoretical example keys)
        \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');
        \Illuminate\Support\Facades\Cache::forget('total_users_count');
        \Illuminate\Support\Facades\Cache::forget('daily_volume');

        // Rebuild costly stats (Simulation)
        $totalUsers = \App\Models\User::count();
        \Illuminate\Support\Facades\Cache::put('total_users_count', $totalUsers, now()->addMinutes(60));
        
        $dailyVolume = \App\Models\Transaction::whereDate('created_at', now()->toDateString())->sum('amount');
        \Illuminate\Support\Facades\Cache::put('daily_volume', $dailyVolume, now()->addMinutes(10));

        $this->info('Cache refreshed successfully.');
        $this->info("Cached Users: {$totalUsers}, Daily Volume: {$dailyVolume}");
    }
}
