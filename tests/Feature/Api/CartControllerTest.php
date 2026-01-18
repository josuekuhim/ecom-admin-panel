<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Drop;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_item_optimistic_guest_creates_cart_and_counts(): void
    {
        $drop = Drop::create(['title' => 'Drop', 'description' => 'D']);
        $product = Product::create([
            'name' => 'Prod',
            'description' => 'Desc',
            'price' => 20,
            'drop_id' => $drop->id,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'V',
            'value' => 'V',
            'stock' => 10,
        ]);

        $response = $this->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(200)->assertJson(['count' => 2]);
        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_update_and_remove_item_authenticated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $drop = Drop::create(['title' => 'Drop', 'description' => 'D']);
        $product = Product::create([
            'name' => 'Prod',
            'description' => 'Desc',
            'price' => 20,
            'drop_id' => $drop->id,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'V',
            'value' => 'V',
            'stock' => 10,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->putJson("/api/cart/items/{$item->id}", ['quantity' => 3])
            ->assertStatus(200);

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 3]);

        $this->deleteJson("/api/cart/items/{$item->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }
}
