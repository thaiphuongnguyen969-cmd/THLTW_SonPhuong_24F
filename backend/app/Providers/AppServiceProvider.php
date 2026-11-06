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
        RateLimiter::for('login', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email', '')));
            $key = hash('sha256', $email.'|'.$request->ip());

            return Limit::perMinute(5)
                ->by($key)
                ->response(fn () => response()->json([
                    'message' => 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau một phút.',
                ], 429));
        });
    }
}
