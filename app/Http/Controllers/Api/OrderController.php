<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with('items.variant.product')->latest()->get();
        return OrderResource::collection($orders);
    }

    public function show(Request $request, $id)
    {
        // Constrain to current user; return 404 if not found/owned
        $orderId = (int) $id;
        Log::info('Order show attempt', [
            'requested_order_id' => $orderId,
            'auth_user_id' => $request->user()->id ?? null,
        ]);
        $owned = $request->user()
            ->orders()
            ->where('orders.id', $orderId)
            ->with('items.variant.product')
            ->first();

        if (!$owned) {
            Log::warning('Order not found or not owned by user', [
                'requested_order_id' => $orderId,
                'auth_user_id' => $request->user()->id ?? null,
            ]);
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->authorize('view', $owned);

    Log::info('Order show success', [
            'order_id' => $owned->id,
            'owner_user_id' => $owned->user_id,
        ]);
        return new OrderResource($owned);
    }

    public function store(CreateOrderRequest $request)
    {
        $cart = Cart::with('items.variant.product')->where('user_id', $request->user()->id)->first();

        Log::info('Order store called', [
            'auth_user_id' => $request->user()->id ?? null,
            'cart_id' => $cart?->id,
            'cart_items_count' => $cart?->items?->count() ?? 0,
        ]);

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $validated = $request->validated();

        return DB::transaction(function () use ($request, $cart, $validated) {
            // Lock variants to avoid race conditions
            $variantIds = $cart->items->pluck('product_variant_id');
            $variants = ProductVariant::whereIn('id', $variantIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($cart->items as $item) {
                $variant = $variants[$item->product_variant_id] ?? null;
                if (!$variant || $variant->stock < $item->quantity) {
                    return response()->json(['message' => 'Not enough stock for one or more items'], 400);
                }
            }

            $itemsTotal = $cart->items->sum(function ($item) {
                return $item->quantity * $item->variant->product->price;
            });
            $shipping = isset($validated['shipping_price']) ? (float)$validated['shipping_price'] : 0.0;
            $totalAmount = $itemsTotal + $shipping;

            $order = $request->user()->orders()->create(array_merge([
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ], $validated));

            Log::info('Order created', [
                'order_id' => $order->id,
                'owner_user_id' => $order->user_id,
                'total_amount' => $totalAmount,
            ]);

            foreach ($cart->items as $item) {
                $variant = $variants[$item->product_variant_id];
                $variant->decrement('stock', $item->quantity);

                $order->items()->create([
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                    'price' => $item->variant->product->price,
                ]);
            }

            $cart->items()->delete();

            Log::info('Cart cleared after order creation', [
                'order_id' => $order->id,
                'cart_id' => $cart->id,
            ]);

            return (new OrderResource($order->load('items.variant.product')))
                ->response()
                ->setStatusCode(201);
        });
    }
}
