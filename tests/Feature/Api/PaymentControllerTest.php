<?php

namespace Tests\Feature\Api;

use App\Contracts\PaymentGateway;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Drop;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_checkout_creates_charge_and_updates_transaction(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $drop = Drop::create(['title' => 'Drop', 'description' => 'Desc']);
        $product = Product::create([
            'name' => 'Prod',
            'description' => 'Desc',
            'price' => 50,
            'drop_id' => $drop->id,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'V1',
            'value' => 'V1',
            'stock' => 5,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => 0,
            'status' => 'pending',
            'shipping_price' => 10,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'price' => 50,
        ]);

        $mock = Mockery::mock(PaymentGateway::class);
        $mock->shouldReceive('createCharge')->once()->andReturn([
            'id' => 'charge_1',
            'status' => 'pending',
            'checkout_url' => 'https://pay',
        ]);
        $this->app->instance(PaymentGateway::class, $mock);

        $response = $this->postJson("/api/checkout/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => 'charge_1'])
            ->assertJsonFragment(['checkout_url' => 'https://pay']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'transaction_id' => 'charge_1',
        ]);
    }
}
