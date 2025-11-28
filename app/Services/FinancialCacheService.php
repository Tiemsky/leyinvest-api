<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Service de gestion du cache pour les indicateurs financiers
 *
 * Supporte:
 * - Redis (production)
 * - Database (local/développement)
 *
 * TTL: 1 heure (configurable)
 */
class FinancialCacheService
{
    private int $ttl;
    private string $prefix;

    public function __construct()
    {
        $this->ttl = config('financial_indicators.cache.ttl', 3600);
        $this->prefix = config('financial_indicators.cache.prefix', 'financial');
    }

    /**
     * Cache les indicateurs d'une action
     */
    public function rememberIndicators(string $actionKey, int $year, string $horizon, callable $callback)
    {
        $key = $this->makeKey('indicators', $actionKey, $year, $horizon);
        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Cache le dashboard complet
     */
    public function rememberDashboard(string $actionKey, int $year, string $horizon, callable $callback)
    {
        $key = $this->makeKey('dashboard', $actionKey, $year, $horizon);
        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Cache les données historiques
     */
    public function rememberHistorical(string $actionKey, int $startYear, int $endYear, string $horizon, callable $callback)
    {
        $key = $this->makeKey('historical', $actionKey, $startYear, $endYear, $horizon);
        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Cache les benchmarks secteur BRVM
     */
    public function rememberBenchmarksBrvm(int $sectorId, int $year, string $horizon, callable $callback)
    {
        $key = $this->makeKey('benchmarks', 'brvm', $sectorId, $year, $horizon);
        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Cache les benchmarks secteur reclassé
     */
    public function rememberBenchmarksSR(int $sectorId, int $year, string $horizon, callable $callback)
    {
        $key = $this->makeKey('benchmarks', 'sr', $sectorId, $year, $horizon);
        return Cache::remember($key, $this->ttl, $callback);
    }

    /**
     * Invalide le cache pour une action
     */
    public function forgetAction(string $actionKey, ?int $year = null): void
    {
        if ($year) {
            // Invalider année spécifique
            foreach (['court_terme', 'moyen_terme', 'long_terme'] as $horizon) {
                Cache::forget($this->makeKey('indicators', $actionKey, $year, $horizon));
                Cache::forget($this->makeKey('dashboard', $actionKey, $year, $horizon));
            }
        } else {
            // Invalider toutes les années
            $pattern = $this->prefix . ':*:' . $actionKey . ':*';
            $this->forgetPattern($pattern);
        }
    }

    /**
     * Invalide le cache des benchmarks pour une année
     */
    public function forgetBenchmarks(int $year): void
    {
        $pattern = $this->prefix . ':benchmarks:*:' . $year . ':*';
        $this->forgetPattern($pattern);
    }

    /**
     * Invalide tout le cache financier
     */
    public function flush(): void
    {
        $pattern = $this->prefix . ':*';
        $this->forgetPattern($pattern);
    }

    /**
     * Construit une clé de cache
     */
    private function makeKey(string ...$parts): string
    {
        return $this->prefix . ':' . implode(':', $parts);
    }

    /**
     * Invalide les clés correspondant à un pattern
     */
    private function forgetPattern(string $pattern): void
    {
        $driver = config('cache.default');

        if ($driver === 'redis') {
            // Redis supporte les patterns
            $keys = Cache::getRedis()->keys($pattern);
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        } else {
            // Fallback: pas de pattern matching pour database cache
            // On flush tout le cache financier
            Cache::flush();
        }
    }
}
