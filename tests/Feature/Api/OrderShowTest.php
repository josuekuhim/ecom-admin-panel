<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderShowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_view_their_own_order_by_id(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => 123.45,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $resp = $this->getJson("/api/orders/{$order->id}");
        $resp->assertOk();
        $resp->assertJsonFragment(['id' => $order->id, 'user_id' => $user->id]);
    }

    #[Test]
    public function user_cannot_view_others_order(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::create([
            'user_id' => $other->id,
            'total_amount' => 99.99,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $resp = $this->getJson("/api/orders/{$order->id}");
        $resp->assertNotFound();
    }
}
