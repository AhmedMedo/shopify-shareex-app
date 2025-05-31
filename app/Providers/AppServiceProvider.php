<?php

namespace App\Providers;

use App\Services\Shareex\ShareexApiService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

// Corrected Namespace and Class

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register ShareexApiService. It will be resolved with the current shop context when injected.
        $this->app->singleton(ShareexApiService::class, function ($app) {
            // The ShareexApiService constructor now handles fetching the authenticated user (shop)
            // or can be passed a shop instance if needed in specific contexts outside of a request cycle.
            return new ShareexApiService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

    }
}

