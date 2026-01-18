<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Drop;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_user_orders(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        Order::create(['user_id' => $user->id, 'total_amount' => 10, 'status' => 'paid']);
        Order::create(['user_id' => $other->id, 'total_amount' => 20, 'status' => 'paid']);

        $this->getJson('/api/orders')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_show_only_allows_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::create(['user_id' => $user->id, 'total_amount' => 10, 'status' => 'paid']);

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/orders/{$order->id}")
            ->assertStatus(404);
    }

    public function test_store_creates_order_from_cart_and_clears_items(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $drop = Drop::create(['title' => 'Drop', 'description' => 'D']);
        $product = Product::create([
            'name' => 'Prod',
            'description' => 'Desc',
            'price' => 30,
            'drop_id' => $drop->id,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'V',
            'value' => 'V',
            'stock' => 5,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->postJson('/api/orders', [
            'shipping_price' => 5,
        ])->assertStatus(201);

        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
        $variant->refresh();
        $this->assertEquals(3, $variant->stock);
    }
}
