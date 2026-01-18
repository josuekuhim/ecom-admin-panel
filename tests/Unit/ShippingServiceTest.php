<?php

namespace Tests\Unit;

use App\Services\ShippingService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShippingServiceTest extends TestCase
{
    #[Test]
    public function it_returns_mock_rates_on_failure(): void
    {
        Http::fake([
            'https://ws.correios.com.br/*' => Http::response('', 500),
        ]);

        $service = new ShippingService();
        $rates = $service->calculate('01001000', 1.0, 20, 10, 15);

        $this->assertArrayHasKey('pac', $rates);
        $this->assertArrayHasKey('sedex', $rates);
        $this->assertSame('25.50', $rates['pac']['price']);
        $this->assertSame('45.70', $rates['sedex']['price']);
    }
}
