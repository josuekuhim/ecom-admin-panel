<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function createProductVariant(int $stock = 10): ProductVariant
    {
        $dropId = \DB::table('drops')->insertGetId([
            'title' => 'Drop',
            'description' => 'Desc',
            'image_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = Product::query()->insertGetId([
            'name' => 'Prod',
            'description' => 'Desc',
            'price' => 100,
            'drop_id' => $dropId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $variantId = ProductVariant::query()->insertGetId([
            'product_id' => $productId,
            'name' => 'Size',
            'value' => 'M',
            'stock' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ProductVariant::findOrFail($variantId);
    }

    #[Test]
    public function user_can_add_and_update_items(): void
    {
        $user = User::factory()->create();
        $variant = $this->createProductVariant(stock: 5);

        Sanctum::actingAs($user);

        // add via optimistic endpoint (route used in API)
        $resp = $this->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
        $resp->assertOk();
        $resp->assertJsonPath('count', 2);

        $cart = Cart::first();
        $item = $cart->items()->first();

        $resp = $this->putJson("/api/cart/items/{$item->id}", [
            'quantity' => 3,
        ]);
        $resp->assertOk();
        $resp->assertJsonPath('data.items_count', 3);
    }

    #[Test]
    public function user_cannot_touch_others_cart_items(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $variant = $this->createProductVariant(stock: 5);

        $cart = Cart::create(['user_id' => $owner->id]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        Sanctum::actingAs($other);
        $resp = $this->deleteJson("/api/cart/items/{$item->id}");
        $resp->assertStatus(403);
    }

    #[Test]
    public function guest_can_add_item_optimistic(): void
    {
        $variant = $this->createProductVariant(stock: 2);

        $resp = $this->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        // unauthenticated should create guest cart via cookie and still succeed
        $resp->assertOk();
        $resp->assertJsonPath('count', 1);
    }
}
