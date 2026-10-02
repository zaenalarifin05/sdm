<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('attendance-kiosk', function (Request $request): Limit {
            $nip = strtoupper(trim((string) $request->input('nip', '')));

            return Limit::perMinute(10)->by(
                ($request->ip() ?? 'unknown').'|'.hash('sha256', $nip)
            );
        });
    }
}
