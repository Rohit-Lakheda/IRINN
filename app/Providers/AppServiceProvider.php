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
        // Set application timezone to IST
        // This will be used by Carbon and all PHP date functions
        date_default_timezone_set('Asia/Kolkata');
        
        // Set Carbon locale
        \Carbon\Carbon::setLocale('en');
    }
}
