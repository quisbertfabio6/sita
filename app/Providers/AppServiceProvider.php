<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

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
        // Limitar por correo + IP, sin bloquear a todos los alumnos que
        // compartan una misma red del instituto.
        RateLimiter::for('sita-login', function (Request $request) {
            $correo = Str::lower(trim((string) $request->input('email')));
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(5)->by('cuenta:' . hash('sha256', $correo . '|' . $ip)),
                Limit::perMinute(30)->by('red:' . $ip),
            ];
        });


    // Agrega esto para forzar HTTPS si estamos en producción o túnel
    if($this->app->environment('production') || request()->header('x-forwarded-proto') == 'https') {
        \URL::forceScheme('https');
        }
    }
}
