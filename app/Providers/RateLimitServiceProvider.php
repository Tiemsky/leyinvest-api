<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        /**
         * ✅ Rate Limiter pour l'authentification
         * 10 tentatives par minute par IP
         */
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Trop de tentatives de connexion. Veuillez réessayer dans quelques minutes.',
                        'code' => 429,
                        'retry_after' => $headers['Retry-After'] ?? 60,
                        'timestamp' => now(),
                    ], 429);
                });
        });

        /**
         * ✅ Rate Limiter strict pour les OTP
         * 3 tentatives par 5 minutes par IP
         */
        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinutes(5, 3)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Trop de tentatives de vérification OTP. Réessayez dans 5 minutes.',
                        'code' => 429,
                        'retry_after' => $headers['Retry-After'] ?? 300,
                        'timestamp' => now(),
                    ], 429);
                });
        });

        /**
         * ✅ Rate Limiter par défaut pour l'API
         * 60 requêtes par minute par utilisateur ou IP
         */
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        /**
         * ✅ Rate Limiter global (toutes routes)
         * 1000 requêtes par minute par IP (protection DDoS)
         */
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(1000)->by($request->ip());
        });
    }
}
