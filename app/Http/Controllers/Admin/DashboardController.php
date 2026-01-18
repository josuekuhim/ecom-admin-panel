<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Drop;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // --- Date Filtering ---
        $period = $request->input('period', '30d');
        $startDate = match ($period) {
            '7d' => now()->subDays(7),
            'this_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            default => now()->subDays(30),
        };
        $endDate = match ($period) {
            'last_month' => now()->subMonth()->endOfMonth(),
            default => now(),
        };

        // --- Stats Cards ---
        $paidOrdersQuery = Order::where('status', 'paid')->whereBetween('created_at', [$startDate, $endDate]);
        
        $totalRevenue = $paidOrdersQuery->sum('total_amount');
        $paidOrdersCount = $paidOrdersQuery->count();
        $averageTicket = ($paidOrdersCount > 0) ? $totalRevenue / $paidOrdersCount : 0;

        // Additional stats
        $pendingOrders = Order::where('status', 'pending')->whereBetween('created_at', [$startDate, $endDate])->count();
        $totalProducts = Product::count();
        $lowStockProducts = Product::whereHas('variants', function($query) {
            $query->where('stock', '<=', 5);
        })->count();
        
        // Cart stats
        $activeCarts = Cart::whereHas('items')->count();
        $abandonedCarts = Cart::whereHas('items')
            ->where('updated_at', '<', now()->subHours(24))
            ->count();

        $stats = [
            'total_revenue' => $totalRevenue,
            'paid_orders_count' => $paidOrdersCount,
            'average_ticket' => $averageTicket,
            'new_users' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'pending_orders' => $pendingOrders,
            'total_products' => $totalProducts,
            'low_stock_products' => $lowStockProducts,
            'active_carts' => $activeCarts,
            'abandoned_carts' => $abandonedCarts,
            // Storefront specific stats
            'storefront_enabled' => config('app.storefront_enabled', true),
            'storefront_url' => config('app.storefront_url', ''),
        ];

        // --- Sales Chart Data ---
        $salesData = Order::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get([
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total')
            ]);
            
        $salesChartData = [
            'labels' => $salesData->pluck('date')->map(fn($date) => Carbon::parse($date)->format('d/m')),
            'values' => $salesData->pluck('total'),
        ];
        
        // --- Top Selling Products ---
        $topSellingProducts = OrderItem::with('variant.product')
            ->whereHas('order', fn($query) => $query->where('status', 'paid')->whereBetween('created_at', [$startDate, $endDate]))
            ->select('product_variant_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_variant_id')
            ->orderBy('total_sold', 'DESC')
            ->limit(5)
            ->get();


        // --- Low Stock Products ---
        $lowStockProductsList = Product::with(['variants' => function($query) {
            $query->where('stock', '<=', 5)->orderBy('stock');
        }])->whereHas('variants', function($query) {
            $query->where('stock', '<=', 5);
        })->limit(10)->get();

        return view('admin.dashboard', compact('stats', 'salesChartData', 'topSellingProducts', 'lowStockProductsList', 'period'));
    }
}
