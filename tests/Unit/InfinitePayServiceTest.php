<?php

namespace Tests\Unit;

use App\Models\Drop;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\InfinitePayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InfinitePayServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_charge_with_items_and_metadata(): void
    {
        config([
            'infinitepay.client_id' => 'client_123',
            'infinitepay.client_secret' => 'secret_456',
            'infinitepay.base_uri' => 'https://api.infinite.test',
        ]);

        Http::fake([
            'https://api.infinite.test/v2/oauth/token' => Http::response(['access_token' => 'token_xyz'], 200),
            'https://api.infinite.test/v2/cobrancas' => Http::response(['id' => 'charge_abc'], 201),
        ]);

        $user = User::factory()->create([
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => 120.50,
            'status' => 'pending',
        ]);

        $drop = Drop::create([
            'title' => 'Drop One',
            'description' => 'Limited run',
        ]);

        $product = Product::create([
            'name' => 'Tee',
            'description' => 'Graphic tee',
            'price' => 60.25,
            'drop_id' => $drop->id,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Size M',
            'value' => 'M',
            'stock' => 5,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'price' => 60.25,
        ]);

        $service = new InfinitePayService();
        $response = $service->createCharge($order, ['channel' => 'web']);

        $this->assertSame('charge_abc', $response['id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.infinite.test/v2/oauth/token'
                && $request['grant_type'] === 'client_credentials'
                && $request['client_id'] === 'client_123';
        });

        Http::assertSent(function ($request) use ($order, $user) {
            if ($request->url() !== 'https://api.infinite.test/v2/cobrancas') {
                return false;
            }

            $payload = $request->data();
            $authHeader = $request->header('Authorization');
            $value = is_array($authHeader) ? ($authHeader[0] ?? '') : $authHeader;

            return $request->hasHeader('Authorization')
                && str_contains((string) $value, 'Bearer token_xyz')
                && $payload['valor'] === $order->total_amount
                && $payload['cliente']['email'] === $user->email
                && $payload['metadata']['order_id'] === $order->id
                && $payload['metadata']['channel'] === 'web'
                && $request->hasHeader('Idempotency-Key');
        });
    }
}
