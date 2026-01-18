<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\AddItemOptimisticRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function show(Request $request)
    {
        $cart = Cart::with('items.variant.product')->firstOrCreate(['user_id' => $request->user()->id]);

        return new CartResource($cart);
    }

    public function addItem(AddCartItemRequest $request)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $this->authorize('update', $cart);

        $variant = ProductVariant::find($request->validated('product_variant_id'));
        if ($variant && $variant->stock < $request->validated('quantity')) {
            return response()->json(['message' => 'Not enough stock'], 400);
        }

        $cartItem = $cart->items()->where('product_variant_id', $request->validated('product_variant_id'))->first();

        if ($cartItem) {
            $cartItem->quantity += $request->validated('quantity');
            $cartItem->save();
        } else {
            $cartItem = $cart->items()->create([
                'product_variant_id' => $request->validated('product_variant_id'),
                'quantity' => $request->validated('quantity'),
            ]);
        }

        return (new CartResource($cart->load('items.variant.product')))
            ->response()
            ->setStatusCode(201);
    }

    public function removeItem(Request $request, $cartItemId)
    {
        $cartItem = CartItem::with('cart')->find($cartItemId);

        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $this->authorize('delete', $cartItem);

        $cartItem->delete();

        return response()->json(null, 204);
    }

    public function updateItem(UpdateCartItemRequest $request, $cartItemId)
    {
        $cartItem = CartItem::with('cart', 'variant')->find($cartItemId);

        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $this->authorize('update', $cartItem);

        $variant = $cartItem->variant;
        $oldQuantity = $cartItem->quantity;
        $newQuantity = $request->validated('quantity');
        $quantityDiff = $newQuantity - $oldQuantity;

        if ($quantityDiff > 0 && $variant && $variant->stock < $quantityDiff) {
            return response()->json(['message' => 'Not enough stock'], 400);
        }

        $cartItem->quantity = $newQuantity;
        $cartItem->save();

        return (new CartResource($cartItem->cart->load('items.variant.product')))
            ->response()
            ->setStatusCode(200);
    }

    public function debug(Request $request)
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();
        $cartItems = CartItem::with('cart')->get();
        
        return response()->json([
            'user_id' => $user->id,
            'cart' => $cart,
            'all_cart_items' => $cartItems->map(function($item) {
                return [
                    'id' => $item->id,
                    'cart_id' => $item->cart_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                    'cart_user_id' => $item->cart ? $item->cart->user_id : null
                ];
            })
        ]);
    }

    /**
     * Add item to cart with optimistic update support
     * Returns simple count for frontend
     */
    public function addItemOptimistic(AddItemOptimisticRequest $request)
    {
        $quantity = $request->input('quantity', 1);
        $productVariantId = $request->validated('product_variant_id');
        $variant = ProductVariant::find($productVariantId);

        if ($variant && $variant->stock < $quantity) {
            return response()->json(['error' => 'Not enough stock'], 400);
        }

        try {
            // Get or create cart (with cookie support for guests)
            $cart = $this->getOrCreateCart($request);
            
            // Check if item already exists
            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_variant_id', $productVariantId)
                ->first();

            if ($existingItem) {
                $existingItem->quantity += $quantity;
                $existingItem->save();
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_variant_id' => $productVariantId,
                    'quantity' => $quantity
                ]);
            }

            // Return simple count
            $totalCount = CartItem::where('cart_id', $cart->id)->sum('quantity');
            
            return response()->json(['count' => $totalCount]);

        } catch (\Exception $e) {
            Log::error('Error adding item to cart', [
                'error' => $e->getMessage(),
                'product_variant_id' => $productVariantId,
            ]);
            
            return response()->json(['error' => 'Failed to add item to cart'], 500);
        }
    }

    /**
     * Get cart count
     */
    public function getCount(Request $request)
    {
        try {
            $cart = $this->getOrCreateCart($request);
            $count = CartItem::where('cart_id', $cart->id)->sum('quantity');
            
            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['count' => 0]);
        }
    }

    /**
     * Get or create cart with guest support via cookies
     */
    private function getOrCreateCart(Request $request)
    {
        // If user is authenticated
        if ($request->user()) {
            return Cart::firstOrCreate(['user_id' => $request->user()->id]);
        }

        // Guest cart with cookie token
        $cartToken = $request->cookie('cart_token');
        
        if (!$cartToken) {
            $cartToken = Str::random(40);
            cookie()->queue('cart_token', $cartToken, 60 * 24 * 30); // 30 days
        }

        return Cart::firstOrCreate(['guest_token' => $cartToken]);
    }
}
