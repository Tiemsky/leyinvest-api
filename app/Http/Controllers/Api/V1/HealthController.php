<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * @tags Health Check
 */
class HealthController extends Controller
{
    /**
     * Basic health check endpoint
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }

    /**
     * Comprehensive health check
     */
    public function check(): JsonResponse
    {
        $checks = [
            'app' => $this->checkApp(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'redis' => $this->checkRedis(),
        ];

        $allHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'healthy');
        $statusCode = $allHealthy ? 200 : 503;

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
        ], $statusCode);
    }

    /**
     * Check application status
     */
    private function checkApp(): array
    {
        try {
            $diskSpace = disk_free_space('/');
            $totalSpace = disk_total_space('/');
            $usedPercentage = (($totalSpace - $diskSpace) / $totalSpace) * 100;

            return [
                'status' => 'healthy',
                'details' => [
                    'version' => config('app.version', '1.0.0'),
                    'disk_usage' => round($usedPercentage, 2).'%',
                    'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2).' MB',
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 2);

            // Test a simple query
            $result = DB::select('SELECT 1 as test');

            return [
                'status' => 'healthy',
                'details' => [
                    'connection' => DB::connection()->getDatabaseName(),
                    'latency' => $latency.' ms',
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache(): array
    {
        try {
            $key = 'health_check_'.time();
            $value = 'test_value';

            // Test cache write
            Cache::put($key, $value, 60);

            // Test cache read
            $retrieved = Cache::get($key);

            // Cleanup
            Cache::forget($key);

            if ($retrieved !== $value) {
                throw new \Exception('Cache read/write mismatch');
            }

            return [
                'status' => 'healthy',
                'details' => [
                    'driver' => config('cache.default'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            $pong = Redis::ping();
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($pong !== true && $pong !== 'PONG') {
                throw new \Exception('Redis ping failed');
            }

            // Get Redis info
            $info = Redis::info();

            return [
                'status' => 'healthy',
                'details' => [
                    'latency' => $latency.' ms',
                    'version' => $info['redis_version'] ?? 'unknown',
                    'connected_clients' => $info['connected_clients'] ?? 'unknown',
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Readiness check for Kubernetes/Docker
     */
    public function ready(): JsonResponse
    {
        // Check if application is ready to serve traffic
        $ready = true;
        $checks = [];

        // Check critical dependencies
        try {
            DB::connection()->getPdo();
            $checks['database'] = true;
        } catch (\Exception $e) {
            $checks['database'] = false;
            $ready = false;
        }

        try {
            Redis::ping();
            $checks['redis'] = true;
        } catch (\Exception $e) {
            $checks['redis'] = false;
            $ready = false;
        }

        return response()->json([
            'ready' => $ready,
            'checks' => $checks,
        ], $ready ? 200 : 503);
    }

    /**
     * Liveness check for Kubernetes/Docker
     */
    public function alive(): JsonResponse
    {
        // Simple check that application is running
        return response()->json([
            'alive' => true,
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }
}
