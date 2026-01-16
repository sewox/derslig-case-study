<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Laravel\Sanctum\Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);
        
        // Register Event Listeners manually since EventServiceProvider is missing/optional in Laravel 11/custom structure
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\SuspiciousActivityDetected::class,
            \App\Listeners\LogSuspiciousActivity::class
        );
    }
}
