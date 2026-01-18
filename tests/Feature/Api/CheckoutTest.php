<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_checkout_their_own_order(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => 50,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        // Stub external call if InfinitePayService uses HTTP client
        if (class_exists(Http::class)) {
            Http::fake();
        }

        $resp = $this->postJson("/api/checkout/{$order->id}");
        $resp->assertStatus(200)->assertJson([]);
    }

    #[Test]
    public function user_cannot_checkout_others_order(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::create([
            'user_id' => $other->id,
            'total_amount' => 50,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);
        $resp = $this->postJson("/api/checkout/{$order->id}");
        $resp->assertStatus(404);
    }
}
