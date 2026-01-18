<?php

namespace Tests\Unit;

use App\Contracts\ClerkClient;
use App\Models\Order;
use App\Models\User;
use App\Data\GoogleUserData;
use App\Services\CustomerLoginService;
use App\Services\CustomerProfileService;
use App\Services\CustomerStatsService;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerService $service;
    private \Mockery\MockInterface $clerk;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clerk = Mockery::mock(ClerkClient::class);
        $this->service = new CustomerService(
            $this->clerk,
            new CustomerProfileService(),
            new CustomerStatsService(),
            new CustomerLoginService(),
        );
    }

    #[Test]
    public function it_creates_customer_from_clerk(): void
    {
        $clerkUserId = 'clerk_123';
        $this->clerk->shouldReceive('getUser')->once()->with($clerkUserId)->andReturn((object) [
            'id' => $clerkUserId,
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'profileImageUrl' => 'https://img/ada.png',
            'primaryEmailAddressId' => 'email_1',
            'emailAddresses' => [
                (object) ['id' => 'email_1', 'emailAddress' => 'ada@example.com'],
            ],
        ]);

        $customer = $this->service->createCustomerFromClerk($clerkUserId);

        $this->assertNotNull($customer);
        $this->assertSame('ada@example.com', $customer->email);
        $this->assertSame('Ada Lovelace', $customer->name);
        $this->assertNotNull($customer->first_login_at);
        // Note: Cart creation is handled by CustomerLoginService, not during customer creation
    }

    #[Test]
    public function it_updates_existing_customer_from_clerk(): void
    {
        $clerkUserId = 'clerk_321';
        $user = User::factory()->create([
            'clerk_user_id' => $clerkUserId,
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'avatar' => null,
        ]);

        $this->clerk->shouldReceive('getUser')->once()->with($clerkUserId)->andReturn((object) [
            'id' => $clerkUserId,
            'firstName' => 'Grace',
            'lastName' => 'Hopper',
            'profileImageUrl' => 'https://img/grace.png',
            'primaryEmailAddressId' => 'email_1',
            'emailAddresses' => [
                (object) ['id' => 'email_1', 'emailAddress' => 'grace@example.com'],
            ],
        ]);

        $result = $this->service->updateCustomerFromClerk($user, $clerkUserId);

        $this->assertTrue($result);
        $user->refresh();
        $this->assertSame('Grace Hopper', $user->name);
        $this->assertSame('grace@example.com', $user->email);
        $this->assertSame('https://img/grace.png', $user->avatar);
        // Note: updateCustomerFromClerk only syncs data, login recording is handled by CustomerLoginService
    }

    #[Test]
    public function it_gets_or_creates_customer_from_clerk(): void
    {
        $clerkUserId = 'clerk_777';

        $existing = User::factory()->create([
            'clerk_user_id' => $clerkUserId,
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->clerk->shouldReceive('getUser')->once()->with($clerkUserId)->andReturn((object) [
            'id' => $clerkUserId,
            'firstName' => 'New',
            'lastName' => 'Name',
            'profileImageUrl' => 'https://img/new.png',
            'primaryEmailAddressId' => 'email_1',
            'emailAddresses' => [
                (object) ['id' => 'email_1', 'emailAddress' => 'new@example.com'],
            ],
        ]);

        $customer = $this->service->createOrGetCustomerFromClerk($clerkUserId);

        $this->assertNotNull($customer);
        $this->assertSame('New Name', $customer->name);
        $this->assertSame('new@example.com', $customer->email);
        $this->assertNotNull($customer->cart);
    }

    #[Test]
    public function it_creates_or_gets_customer_from_google(): void
    {
        $googleData = new GoogleUserData(
            google_id: 'google_123',
            name: 'Gina Google',
            email: 'gina@example.com',
            image: 'https://img/gina.png',
        );

        $customer = $this->service->createOrGetCustomerFromGoogle($googleData);

        $this->assertNotNull($customer);
        $this->assertSame('google_123', $customer->google_id);
        $this->assertSame('Gina Google', $customer->name);
        $this->assertNotNull($customer->cart);

        // Second call should return existing user and keep cart
        $again = $this->service->createOrGetCustomerFromGoogle($googleData);
        $this->assertSame($customer->id, $again->id);
        $this->assertSame($customer->cart->id, $again->cart->id);
    }

    #[Test]
    public function it_returns_customer_stats(): void
    {
        $user = User::factory()->create([
            'name' => 'Stat User',
            'email' => 'stat@example.com',
            'address' => 'Street',
            'city' => 'City',
            'state' => 'ST',
            'zip_code' => '00000-000',
        ]);

        Order::create([
            'user_id' => $user->id,
            'total_amount' => 100,
            'status' => 'paid',
        ]);

        Order::create([
            'user_id' => $user->id,
            'total_amount' => 50,
            'status' => 'paid',
        ]);

        $stats = $this->service->getCustomerStats($user->fresh());

        $this->assertSame(2, $stats['total_orders']);
        $this->assertSame(150.0, $stats['total_spent']);
        $this->assertTrue($stats['has_complete_profile']);
    }
}
