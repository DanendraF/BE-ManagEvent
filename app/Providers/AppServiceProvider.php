<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
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
        // Detect and prevent N+1 queries during development
        Model::preventLazyLoading(! $this->app->isProduction());

        // 1. Auth Rate Limiter: max 5 attempts per minute per email + IP
        RateLimiter::for('auth', function (Request $request) {
            $email = (string) $request->input('email', '');
            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        // 2. Event Registration Rate Limiter: max 10 requests per minute per user
        RateLimiter::for('registrations', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // 3. QR Scanner Rate Limiter: max 120 scans per minute per organizer
        RateLimiter::for('scanner', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
