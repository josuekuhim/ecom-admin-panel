<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Cart;
use Carbon\Carbon;
use App\Models\ProductImage;

class StorefrontController extends Controller
{
    public function index()
    {
        // Estatísticas da storefront
        $stats = [
            'total_customers' => User::count(),
            'active_sessions' => Cart::where('updated_at', '>=', Carbon::now()->subHour())->count(),
            'orders_today' => Order::whereDate('created_at', Carbon::today())->count(),
            'revenue_today' => Order::whereDate('created_at', Carbon::today())
                ->where('status', 'paid')
                ->sum('total_amount'),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'conversion_rate' => $this->calculateConversionRate(),
        ];

        // Produtos mais visualizados / recentes
        $topProducts = Product::withCount(['variants'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        if ($topProducts->isNotEmpty()) {
            $ids = $topProducts->pluck('id');
            $imageCounts = ProductImage::on('sqlite_images')
                ->whereIn('product_id', $ids)
                ->selectRaw('product_id, COUNT(*) as aggregate')
                ->groupBy('product_id')
                ->pluck('aggregate', 'product_id');

            $topProducts->transform(function ($p) use ($imageCounts) {
                $p->images_count = (int)($imageCounts[$p->id] ?? 0);
                return $p;
            });
        }

        // Pedidos recentes
        $recentOrders = Order::with(['user', 'items.variant.product'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.storefront.index', compact('stats', 'topProducts', 'recentOrders'));
    }

    public function settings()
    {
        $settings = [
            'storefront_url' => env('STOREFRONT_URL', ''),
            'storefront_enabled' => env('STOREFRONT_ENABLED', true),
            'maintenance_mode' => env('STOREFRONT_MAINTENANCE', false),
            'api_rate_limit' => env('API_RATE_LIMIT', 60),
        ];

        return view('admin.storefront.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'storefront_url' => 'nullable|url',
            'storefront_enabled' => 'boolean',
            'maintenance_mode' => 'boolean',
            'api_rate_limit' => 'integer|min:10|max:1000',
        ]);

        $this->updateEnvFile([
            'STOREFRONT_URL' => $request->storefront_url,
            'STOREFRONT_ENABLED' => $request->storefront_enabled ? 'true' : 'false',
            'STOREFRONT_MAINTENANCE' => $request->maintenance_mode ? 'true' : 'false',
            'API_RATE_LIMIT' => $request->api_rate_limit,
        ]);

        return redirect()->route('admin.storefront.settings')
            ->with('success', 'Configurações da storefront atualizadas com sucesso!');
    }

    public function apiStatus()
    {
        $endpoints = [
            'Autenticação' => '/api/login',
            'Produtos' => '/api/products',
            'Carrinho' => '/api/cart',
            'Pedidos' => '/api/orders',
            'Pagamento' => '/api/checkout/1',
        ];

        $status = [];
        $baseUrl = config('app.url');

        foreach ($endpoints as $name => $endpoint) {
            try {
                $response = Http::timeout(5)->get($baseUrl . $endpoint);
                $status[$name] = [
                    'status' => $response->successful() || $response->status() === 401 ? 'online' : 'error',
                    'response_time' => $response->transferStats?->getTransferTime() ?? 0,
                    'status_code' => $response->status(),
                ];
            } catch (\Exception $e) {
                $status[$name] = [
                    'status' => 'offline',
                    'response_time' => 0,
                    'status_code' => 0,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json($status);
    }

    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            
            return response()->json([
                'success' => true,
                'message' => 'Cache limpo com sucesso!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao limpar cache: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateConversionRate()
    {
        $totalCarts = Cart::count();
        $completedOrders = Order::where('status', 'paid')->count();
        
        if ($totalCarts === 0) {
            return 0;
        }
        
        return round(($completedOrders / $totalCarts) * 100, 2);
    }

    private function updateEnvFile($data)
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envFile, $envContent);
        
        // Clear config cache to reload new values
        Artisan::call('config:clear');
    }
}
