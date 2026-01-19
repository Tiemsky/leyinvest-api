<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

// Implementation de securitee pour eviter les attaques brutes forces, et email spamming, DDOS,
class RateLimitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/rate-limits.php', 'rate-limits');
    }

    public function boot(): void
    {

        // 🛡️ Limiteur GLOBAL (DDoS)
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(env('RATE_LIMIT_GLOBAL', 1000))
                ->by($request->ip())
                ->response(fn () => response()->json([
                    'success' => false,
                    'message' => 'Protection Anti-DDoS : Trop de requêtes.',
                ], 429));
        });

        // 🔑 Limiteur AUTH (Login/Register)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // 📱 Limiteur API (Usage standard)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // 📧 Limiteur OTP (3 tentatives max par 5 minutes)
        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinutes(5, 3)->by($request->input('email') ?: $request->ip());
        });

        // Limite Inscription
        RateLimiter::for('register', function (Request $request) {
            return $this->buildLimit('register')->by($request->ip());
        });
    }

    private function buildLimit(string $name): Limit
    {
        $config = config("rate-limits.{$name}");

        // On utilise perMinutes par défaut pour supporter les fenêtres de 1min ou plus
        return Limit::perMinutes($config['window'], $config['max'])->response(function (Request $request, array $headers) use ($name) {
            return response()->json([
                'success' => false,
                'message' => $this->getErrorMessage($name),
                'code' => 429,
                'retry_after' => $headers['Retry-After'] ?? 60,
                'timestamp' => now()->toIso8601String(),
            ], 429, $headers);
        });
    }

    private function getErrorMessage(string $type): string
    {
        return match ($type) {
            'auth' => 'Trop de tentatives de connexion.',
            'otp' => 'Trop de demandes de code. Réessayez plus tard.',
            'register' => 'Trop de comptes créés depuis cette IP.',
            default => 'Trop de requêtes.',
        };
    }
}
