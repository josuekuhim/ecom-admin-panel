<?php

namespace Tests\Feature\Api;

use App\Services\ClerkWebhookHandler;
use App\Services\ClerkWebhookVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ClerkWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_missing_secret_returns_500(): void
    {
        config(['clerk.webhook_secret' => null]);

        $this->postJson('/api/webhooks/clerk', ['type' => 'user.created'])
            ->assertStatus(500);
    }

    public function test_valid_signature_dispatches_handler(): void
    {
        config(['clerk.webhook_secret' => 'whsec_test']);

        $verifier = Mockery::mock(ClerkWebhookVerifier::class);
        $verifier->shouldReceive('verify')->once()->andReturn(true);
        $this->app->instance(ClerkWebhookVerifier::class, $verifier);

        $handler = Mockery::mock(ClerkWebhookHandler::class);
        $handler->shouldReceive('dispatch')->once()->with('user.created', ['id' => 'clerk_123']);
        $this->app->instance(ClerkWebhookHandler::class, $handler);

        $payload = [
            'type' => 'user.created',
            'data' => ['id' => 'clerk_123'],
        ];

        $this->postJson('/api/webhooks/clerk', $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'received']);
    }
}
