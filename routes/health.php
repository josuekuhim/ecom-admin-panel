<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Advanced health check endpoint
 * Returns detailed information about application status
 */
Route::get('/health', function (): JsonResponse {
    $health = [
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment(),
        'version' => '1.0.0',
        'checks' => []
    ];

    // Database check
    try {
        DB::connection()->getPdo();
        $health['checks']['database'] = [
            'status' => 'healthy',
            'connection' => DB::connection()->getName(),
            'response_time' => $this->measureTime(function () {
                return DB::select('SELECT 1')[0] ?? null;
            })
        ];
    } catch (Exception $e) {
        $health['checks']['database'] = [
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ];
        $health['status'] = 'degraded';
    }

    // Cache check
    try {
        $testKey = 'health_check_' . time();
        Cache::put($testKey, 'test', 60);
        $retrieved = Cache::get($testKey);
        Cache::forget($testKey);
        
        $health['checks']['cache'] = [
            'status' => $retrieved === 'test' ? 'healthy' : 'unhealthy',
            'driver' => config('cache.default')
        ];
    } catch (Exception $e) {
        $health['checks']['cache'] = [
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ];
        $health['status'] = 'degraded';
    }

    // Storage check
    try {
        $testFile = 'health_check_' . time() . '.txt';
        $path = storage_path('app/' . $testFile);
        file_put_contents($path, 'test');
        $content = file_get_contents($path);
        unlink($path);
        
        $health['checks']['storage'] = [
            'status' => $content === 'test' ? 'healthy' : 'unhealthy',
            'writable' => is_writable(storage_path('app'))
        ];
    } catch (Exception $e) {
        $health['checks']['storage'] = [
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ];
        $health['status'] = 'degraded';
    }

    // Memory usage
    $health['checks']['memory'] = [
        'status' => 'healthy',
        'usage' => memory_get_usage(true),
        'peak' => memory_get_peak_usage(true),
        'limit' => ini_get('memory_limit')
    ];

    // Application info
    $health['application'] = [
        'name' => config('app.name'),
        'url' => config('app.url'),
        'locale' => config('app.locale'),
        'timezone' => config('app.timezone'),
        'debug' => config('app.debug')
    ];

    // Service integrations status
    $health['integrations'] = [
        'clerk' => [
            'configured' => !empty(config('clerk.secret_key')),
            'status' => !empty(config('clerk.secret_key')) ? 'configured' : 'not_configured'
        ],
        'infinitepay' => [
            'configured' => !empty(config('infinitepay.client_id')),
            'status' => !empty(config('infinitepay.client_id')) ? 'configured' : 'not_configured'
        ]
    ];

    // Return appropriate HTTP status
    $httpStatus = match($health['status']) {
        'ok' => 200,
        'degraded' => 200, // Still operational
        'unhealthy' => 503,
        default => 200
    };

    return response()->json($health, $httpStatus);
});

// Simple uptime check
Route::get('/ping', function (): JsonResponse {
    return response()->json([
        'message' => 'pong',
        'timestamp' => now()->toISOString()
    ]);
});

// Readiness probe (for container orchestration)
Route::get('/ready', function (): JsonResponse {
    try {
        // Check if essential services are ready
        DB::connection()->getPdo();
        
        return response()->json([
            'status' => 'ready',
            'timestamp' => now()->toISOString()
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'not_ready',
            'error' => $e->getMessage(),
            'timestamp' => now()->toISOString()
        ], 503);
    }
});

// Liveness probe (for container orchestration)
Route::get('/alive', function (): JsonResponse {
    return response()->json([
        'status' => 'alive',
        'timestamp' => now()->toISOString()
    ]);
});

/**
 * Helper function to measure execution time
 */
function measureTime(callable $callback): float {
    $start = microtime(true);
    $callback();
    return round((microtime(true) - $start) * 1000, 2); // milliseconds
}