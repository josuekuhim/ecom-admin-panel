<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_signature_is_rejected(): void
    {
        config(['infinitepay.webhook_secret' => 'secret']);

        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => 10,
            'status' => 'pending',
            'transaction_id' => 'tx1',
        ]);

        $payload = ['event_type' => 'transaction_paid', 'data' => ['transaction_id' => 'tx1']];

        $this->postJson('/api/webhooks/infinitepay', $payload)
            ->assertStatus(401);

        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }

    public function test_valid_signature_updates_order_to_paid(): void
    {
        config(['infinitepay.webhook_secret' => 'secret']);

        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => 10,
            'status' => 'pending',
            'transaction_id' => 'tx2',
        ]);

        $payloadArray = ['event_type' => 'transaction_paid', 'data' => ['transaction_id' => 'tx2']];
        $json = json_encode($payloadArray);
        $signature = hash_hmac('sha256', $json, 'secret');

        $this->call(
            'POST',
            '/api/webhooks/infinitepay',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X-INFINITEPAY-SIGNATURE' => $signature,
                'REQUEST_METHOD' => 'POST',
            ],
            $json
        )->assertStatus(200);

        $order->refresh();
        $this->assertEquals('paid', $order->status);
    }
}
