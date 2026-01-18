<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class MonitoringController extends Controller
{
    /**
     * Render the API Monitoring page.
     */
    public function index()
    {
        return view('admin.monitoring.api');
    }

    /**
     * JSON: Collect health/status of core services and key API endpoints.
     */
    public function status(Request $request)
    {
        $startedAt = microtime(true);

        $appUrl = config('app.url');
        if (empty($appUrl)) {
            $appUrl = URL::to('/');
        }

        $endpoints = [
            [
                'name' => 'Health (/up)',
                'url' => rtrim($appUrl, '/') . '/up',
                'method' => 'GET',
            ],
            [
                'name' => 'Login',
                'url' => rtrim($appUrl, '/') . '/api/login',
                'method' => 'POST',
            ],
            [
                'name' => 'Produtos',
                'url' => rtrim($appUrl, '/') . '/api/products',
                'method' => 'GET',
            ],
            [
                'name' => 'Carrinho',
                'url' => rtrim($appUrl, '/') . '/api/cart',
                'method' => 'GET',
            ],
            [
                'name' => 'Pedidos',
                'url' => rtrim($appUrl, '/') . '/api/orders',
                'method' => 'GET',
            ],
        ];

        $endpointResults = [];
        $onlineCount = 0;
        foreach ($endpoints as $e) {
            $t0 = microtime(true);
            try {
                $req = Http::timeout(8);
                $method = strtoupper($e['method']);
                $response = $method === 'POST' ? $req->post($e['url'], []) : $req->get($e['url']);

                // Measure elapsed request time in seconds
                $transferTime = microtime(true) - $t0;

                // Consider endpoint reachable if HTTP status is a typical API code (even 401/403/422)
                $reachableCodes = [200, 201, 202, 204, 301, 302, 400, 401, 403, 404, 409, 422];
                $isOnline = in_array($response->status(), $reachableCodes, true);
                if ($isOnline) $onlineCount++;

                $endpointResults[] = [
                    'name' => $e['name'],
                    'url' => $e['url'],
                    'method' => $method,
                    'status' => $isOnline ? 'online' : 'error',
                    'status_code' => $response->status(),
                    'time_ms' => (int) round($transferTime * 1000),
                ];
            } catch (Throwable $ex) {
                $endpointResults[] = [
                    'name' => $e['name'],
                    'url' => $e['url'],
                    'method' => strtoupper($e['method']),
                    'status' => 'offline',
                    'status_code' => 0,
                    'time_ms' => (int) round((microtime(true) - $t0) * 1000),
                    'error' => Str::limit($ex->getMessage(), 200),
                ];
            }
        }

        // Core services
        $services = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
            'mail' => $this->checkMail(),
            // 'clerk' removed per request
            'infinitepay' => ['status' => 'ok'],
        ];

        $criticalServices = ['database', 'cache', 'storage'];
        $hasCriticalDown = collect($services)
            ->only($criticalServices)
            ->contains(function ($s) { return $s['status'] !== 'ok'; });

        $overall = $hasCriticalDown ? 'down' : ($onlineCount === count($endpoints) ? 'healthy' : 'degraded');

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'app' => [
                'env' => app()->environment(),
                'debug' => (bool) config('app.debug'),
                'url' => $appUrl,
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
            ],
            'overall' => $overall,
            'metrics' => [
                'endpoints_online' => $onlineCount,
                'endpoints_total' => count($endpoints),
                'duration_ms' => $durationMs,
                'avg_response_ms' => count($endpointResults) > 0
                    ? (int) round(collect($endpointResults)->avg('time_ms'))
                    : 0,
            ],
            'services' => $services,
            'endpoints' => $endpointResults,
        ]);
    }

    private function checkDatabase(): array
    {
        $t0 = microtime(true);
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'ok',
                'driver' => config('database.default'),
                'time_ms' => (int) round((microtime(true) - $t0) * 1000),
            ];
        } catch (Throwable $ex) {
            return [
                'status' => 'error',
                'driver' => config('database.default'),
                'time_ms' => (int) round((microtime(true) - $t0) * 1000),
                'error' => Str::limit($ex->getMessage(), 200),
            ];
        }
    }

    private function checkCache(): array
    {
        $t0 = microtime(true);
        try {
            $key = 'monitoring_ping';
            Cache::put($key, 'ok', 10);
            $val = Cache::get($key);
            $ok = $val === 'ok';
            return [
                'status' => $ok ? 'ok' : 'error',
                'driver' => config('cache.default'),
                'time_ms' => (int) round((microtime(true) - $t0) * 1000),
            ];
        } catch (Throwable $ex) {
            return [
                'status' => 'error',
                'driver' => config('cache.default'),
                'time_ms' => (int) round((microtime(true) - $t0) * 1000),
                'error' => Str::limit($ex->getMessage(), 200),
            ];
        }
    }

    private function checkStorage(): array
    {
        $t0 = microtime(true);
        try {
            $disk = 'local';
            $path = 'monitoring_' . Str::random(6) . '.txt';
            Storage::disk($disk)->put($path, 'ok');
            $exists = Storage::disk($disk)->exists($path);
            Storage::disk($disk)->delete($path);
            return [
                'status' => $exists ? 'ok' : 'error',
                'disk' => $disk,
                'time_ms' => (int) round((microtime(true) - $t0) * 1000),
            ];
        } catch (Throwable $ex) {
            return [
                'status' => 'error',
                'disk' => 'local',
                'time_ms' => (int) round((microtime(true) - $t0) * 1000),
                'error' => Str::limit($ex->getMessage(), 200),
            ];
        }
    }

    private function checkQueue(): array
    {
        $driver = config('queue.default');
        return [
            'status' => $driver ? 'ok' : 'error',
            'driver' => $driver,
        ];
    }

    private function checkMail(): array
    {
        $mailer = config('mail.default');
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $configured = !empty($mailer) && !empty($host) && !empty($port);
        return [
            'status' => $configured ? 'ok' : 'not_configured',
            'mailer' => $mailer,
        ];
    }

    // Clerk check removed
    // InfinitePay forced ok per request
}
