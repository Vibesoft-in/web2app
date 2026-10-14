<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthController extends Controller
{
    /**
     * Comprehensive health check endpoint for container probes, uptime monitoring, and load balancers.
     * Evaluates Database, Cache/Redis, and Storage subsystems.
     */
    public function check(): JsonResponse
    {
        $startTime = microtime(true);
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $isHealthy = collect($checks)->every(fn (array $check) => $check['status'] === 'ok');
        $statusCode = $isHealthy ? 200 : 503;

        $totalDurationMs = round((microtime(true) - $startTime) * 1000, 2);

        return response()->json([
            'status' => $isHealthy ? 'ok' : 'degraded',
            'environment' => config('app.env'),
            'timestamp' => now()->toIso8601String(),
            'response_time_ms' => $totalDurationMs,
            'checks' => $checks,
            'system' => [
                'php_version' => PHP_VERSION,
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ],
        ], $statusCode);
    }

    /**
     * Check Database connectivity and response time.
     *
     * @return array{status: string, message?: string, latency_ms?: float}
     */
    protected function checkDatabase(): array
    {
        $start = microtime(true);
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');

            return [
                'status' => 'ok',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check Cache / Redis read-write capability.
     *
     * @return array{status: string, message?: string, latency_ms?: float}
     */
    protected function checkCache(): array
    {
        $start = microtime(true);
        $key = 'health:probe:'.uniqid();
        try {
            Cache::put($key, 'ok', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            if ($value !== 'ok') {
                return [
                    'status' => 'error',
                    'message' => 'Cache value mismatch.',
                ];
            }

            return [
                'status' => 'ok',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Cache check failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check local storage writeability.
     *
     * @return array{status: string, message?: string, latency_ms?: float}
     */
    protected function checkStorage(): array
    {
        $start = microtime(true);
        $path = 'health_probe_'.uniqid().'.txt';
        try {
            Storage::disk('local')->put($path, 'probe');
            $read = Storage::disk('local')->get($path);
            Storage::disk('local')->delete($path);

            if ($read !== 'probe') {
                return [
                    'status' => 'error',
                    'message' => 'Storage read mismatch.',
                ];
            }

            return [
                'status' => 'ok',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Storage check failed: '.$e->getMessage(),
            ];
        }
    }
}
