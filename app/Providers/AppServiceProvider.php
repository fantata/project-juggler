<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Public client board: cap page loads and file streams per IP so a
        // leaked/guessed link can't be hammered or scraped en masse.
        RateLimiter::for('client-board', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
