<?php

namespace Tests\Feature\Api;

use App\Actions\AuthenticateWithClerkAction;
use App\Actions\VerifyClerkSessionAction;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_register_creates_user_and_token(): void
    {
        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type']);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_login_returns_token(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type']);
    }

    public function test_complete_profile_updates_customer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile', [
                'address' => 'Rua 1',
                'city' => 'SP',
                'state' => 'SP',
                'zip_code' => '00000000',
            ])
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Profile updated successfully']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'address' => 'Rua 1',
            'city' => 'SP',
            'state' => 'SP',
            'zip_code' => '00000000',
        ]);
    }

    public function test_clerk_auth_uses_customer_service(): void
    {
        $customer = User::factory()->create([
            'clerk_user_id' => 'clerk_1',
        ]);

        // Mock CustomerService which is used by AuthenticateWithClerkAction
        $customerServiceMock = Mockery::mock(CustomerService::class);
        $customerServiceMock->shouldReceive('createOrGetCustomerFromClerk')
            ->once()
            ->with('clerk_1')
            ->andReturn($customer);
        $customerServiceMock->shouldReceive('getCustomerStats')
            ->once()
            ->with(Mockery::type(User::class))
            ->andReturn(['total_orders' => 0]);

        $this->app->instance(CustomerService::class, $customerServiceMock);

        $response = $this->postJson('/api/auth/clerk', ['clerk_user_id' => 'clerk_1']);

        $response->assertStatus(200)
            ->assertJsonFragment(['clerk_user_id' => 'clerk_1']);
    }

    public function test_verify_clerk_session_returns_customer(): void
    {
        $customer = User::factory()->create([
            'clerk_user_id' => 'clerk_2',
        ]);

        // Mock CustomerService which is used by VerifyClerkSessionAction
        $customerServiceMock = Mockery::mock(CustomerService::class);
        $customerServiceMock->shouldReceive('createOrGetCustomerFromClerk')
            ->once()
            ->with('clerk_2')
            ->andReturn($customer);

        $this->app->instance(CustomerService::class, $customerServiceMock);

        $response = $this->postJson('/api/auth/clerk/verify', ['clerk_user_id' => 'clerk_2']);

        $response->assertStatus(200)
            ->assertJsonFragment(['clerk_user_id' => 'clerk_2']);
    }
}
