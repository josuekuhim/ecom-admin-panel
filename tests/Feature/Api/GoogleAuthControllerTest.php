<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_auth_creates_user_and_token(): void
    {
        $payload = [
            'google_id' => 'google_123',
            'email' => 'gina@example.com',
            'name' => 'Gina',
            'image' => 'https://img/gina.png',
        ];

        $response = $this->postJson('/api/auth/google', $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['google_id' => 'google_123'])
            ->assertJsonStructure(['access_token']);

        $this->assertDatabaseHas('users', ['email' => 'gina@example.com']);
    }

    public function test_get_current_customer_returns_data(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google_999',
            'email' => 'user@example.com',
            'name' => 'User',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/customer')
            ->assertStatus(200)
            ->assertJsonFragment(['google_id' => 'google_999']);
    }
}
