<?php

namespace Tests\Feature\Api;

use App\Contracts\ShippingGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_shipping_returns_options(): void
    {
        $mock = \Mockery::mock(ShippingGateway::class);
        $mock->shouldReceive('calculate')->once()->andReturn([
            'pac' => ['price' => '10.00'],
        ]);

        $this->app->instance(ShippingGateway::class, $mock);

        $response = $this->postJson('/api/shipping/calculate', [
            'cep' => '01001000',
            'weight' => 1,
            'length' => 20,
            'height' => 10,
            'width' => 15,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['price' => '10.00']);
    }
}
