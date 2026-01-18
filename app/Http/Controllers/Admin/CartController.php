<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display a listing of active carts.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $carts = Cart::with(['user', 'items.variant.product'])
            ->whereHas('items') // Only carts with items
            ->when($search, function ($query, $search) {
                return $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest('updated_at')
            ->paginate(15);

        return view('admin.carts.index', compact('carts', 'search'));
    }

    /**
     * Display the specified cart.
     */
    public function show(Cart $cart)
    {
        $cart->load(['user', 'items.variant.product']);
        
        // Calculate cart total
        $cartTotal = $cart->items->sum(function ($item) {
            return $item->quantity * $item->variant->product->price;
        });

        return view('admin.carts.show', compact('cart', 'cartTotal'));
    }

    /**
     * Get cart analytics data.
     */
    public function analytics()
    {
        $stats = [
            'active_carts' => Cart::whereHas('items')->count(),
            'abandoned_carts' => Cart::whereHas('items')
                ->where('updated_at', '<', now()->subHours(24))
                ->count(),
            'total_cart_value' => Cart::whereHas('items')
                ->get()
                ->sum(function ($cart) {
                    return $cart->items->sum(function ($item) {
                        return $item->quantity * $item->variant->product->price;
                    });
                }),
        ];

        return response()->json($stats);
    }
}
