<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all admin notifications
     */
    public function index()
    {
        $notifications = [];

        // Low stock notifications
        $lowStockProducts = Product::with(['variants' => function($query) {
            $query->where('stock', '<=', 5)->orderBy('stock');
        }])->whereHas('variants', function($query) {
            $query->where('stock', '<=', 5);
        })->get();

        foreach ($lowStockProducts as $product) {
            foreach ($product->variants as $variant) {
                $notifications[] = [
                    'type' => 'low_stock',
                    'severity' => $variant->stock == 0 ? 'danger' : 'warning',
                    'title' => $variant->stock == 0 ? 'Out of Stock' : 'Low Stock',
                    'message' => "{$product->name} - {$variant->name}: {$variant->value} ({$variant->stock} remaining)",
                    'page' => 'Produtos',
                    'source' => 'products',
                    'action_url' => route('admin.variants.edit', $variant),
                    'action_text' => 'Update Stock',
                    'created_at' => $variant->updated_at,
                ];
            }
        }

        // Abandoned cart notifications (last 24 hours)
        $abandonedCarts = Cart::with('user')
            ->whereHas('items')
            ->where('updated_at', '>=', now()->subDays(7))
            ->where('updated_at', '<', now()->subHours(24))
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        foreach ($abandonedCarts as $cart) {
            $notifications[] = [
                'type' => 'abandoned_cart',
                'severity' => 'info',
                'title' => 'Abandoned Cart',
                'message' => "Customer {$cart->user->name} hasn't updated cart for " . $cart->updated_at->diffForHumans(),
                'page' => 'Carrinhos',
                'source' => 'carts',
                'action_url' => route('admin.carts.show', $cart),
                'action_text' => 'View Cart',
                'created_at' => $cart->updated_at,
            ];
        }

        // Pending orders notifications
        $pendingOrders = Order::with('user')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($pendingOrders as $order) {
            $notifications[] = [
                'type' => 'pending_order',
                'severity' => 'warning',
                'title' => 'Pending Payment',
                'message' => "Order #{$order->id} from {$order->user->name} is awaiting payment",
                'page' => 'Pedidos',
                'source' => 'orders',
                'action_url' => route('admin.orders.show', $order),
                'action_text' => 'View Order',
                'created_at' => $order->created_at,
            ];
        }

        // Sort by created_at desc
        usort($notifications, function($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });

        return response()->json($notifications);
    }

    /**
     * Get notification counts
     */
    public function counts()
    {
        $counts = [
            'low_stock' => Product::whereHas('variants', function($query) {
                $query->where('stock', '<=', 5);
            })->count(),
            'abandoned_carts' => Cart::whereHas('items')
                ->where('updated_at', '<', now()->subHours(24))
                ->count(),
            'pending_orders' => Order::where('status', 'pending')
                ->where('created_at', '>=', now()->subHours(24))
                ->count(),
        ];

        $counts['total'] = array_sum($counts);

        return response()->json($counts);
    }
}
