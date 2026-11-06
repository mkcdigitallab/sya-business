<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Limites de tentatives (nombre / fenêtre en minutes) : elles protègent les formulaires publics.
        RateLimiter::for('checkout', fn (Request $r) => Limit::perMinutes(15, 10)->by('checkout:'.$r->ip()));
        RateLimiter::for('inquiry', fn (Request $r) => Limit::perMinutes(60, 8)->by('inquiry:'.$r->ip()));
        RateLimiter::for('track', fn (Request $r) => Limit::perMinutes(15, 200)->by('track:'.$r->ip()));
        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinutes(15, 10)->by('login:ip:'.$r->ip()),
            Limit::perMinutes(15, 5)->by('login:email:'.Str::lower((string) $r->input('email'))),
        ]);
        RateLimiter::for('admin', fn (Request $r) => Limit::perMinutes(15, 300)->by('admin:'.($r->user()?->id ?? $r->ip())));
    }
}
