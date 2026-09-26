<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        $this->configureRateLimiting();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Named limiters for actions that either trigger a live RouterOS call
     * or dispatch a heavier background job — spec section 15 lists rate
     * limiting as a WAJIB (required) hardening item. Keyed by user id
     * (falling back to IP) so one account can't starve others sharing the
     * same NAT/office network.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('voucher-generate', function ($request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('mikrotik-test', function ($request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('pdf-export', function ($request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
