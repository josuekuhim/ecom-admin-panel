<?php

namespace Tests\Unit;

use App\Contracts\ClerkAuthenticator;
use App\Contracts\CustomerLoginHandler;
use App\Contracts\CustomerProvisioner;
use App\Data\ClerkUserData;
use App\Exceptions\Domain\AuthenticationException;
use App\Http\Middleware\ClerkAuthMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClerkAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createMiddleware(
        ?object $authService = null,
        ?object $provisioningService = null,
        ?object $loginService = null,
    ): ClerkAuthMiddleware {
        return new ClerkAuthMiddleware(
            $authService ?? Mockery::mock(ClerkAuthenticator::class),
            $provisioningService ?? Mockery::mock(CustomerProvisioner::class),
            $loginService ?? Mockery::mock(CustomerLoginHandler::class),
        );
    }

    #[Test]
    public function it_requires_bearer_token(): void
    {
        $authService = Mockery::mock(ClerkAuthenticator::class);
        // No call expected since token is missing

        $middleware = $this->createMiddleware($authService);

        $request = Request::create('/api/test', 'GET');
        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('required', strtolower($response->getContent()));
    }

    #[Test]
    public function it_rejects_invalid_clerk_token(): void
    {
        $authService = Mockery::mock(ClerkAuthenticator::class);
        $authService->shouldReceive('authenticateToken')
            ->once()
            ->with('bad-token')
            ->andThrow(AuthenticationException::invalidToken());

        $middleware = $this->createMiddleware($authService);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer bad-token');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('invalid', strtolower($response->getContent()));
    }

    #[Test]
    public function it_sets_authenticated_user_when_found(): void
    {
        $user = User::factory()->create([
            'clerk_user_id' => 'clerk-123',
            'email' => 'existing@example.com',
            'name' => 'Existing User',
        ]);

        $clerkUserData = new ClerkUserData(
            id: 'clerk-123',
            email: 'existing@example.com',
            firstName: 'Existing',
            lastName: 'User',
        );

        $authService = Mockery::mock(ClerkAuthenticator::class);
        $authService->shouldReceive('authenticateToken')
            ->once()
            ->with('good-token')
            ->andReturn($clerkUserData);

        $provisioningService = Mockery::mock(CustomerProvisioner::class);
        $provisioningService->shouldReceive('provision')
            ->once()
            ->with(Mockery::type(ClerkUserData::class))
            ->andReturn($user);

        $loginService = Mockery::mock(CustomerLoginHandler::class);
        $loginService->shouldReceive('recordLogin')
            ->once()
            ->with(Mockery::type(User::class));

        $middleware = $this->createMiddleware($authService, $provisioningService, $loginService);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer good-token');

        $response = $middleware->handle($request, function ($req) {
            $this->assertNotNull($req->user());
            return response()->json(['ok' => true]);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(auth()->user()->is($user));
    }

    #[Test]
    public function it_creates_user_when_missing(): void
    {
        $newUser = User::factory()->create([
            'clerk_user_id' => 'clerk-789',
            'email' => 'new@example.com',
            'name' => 'New User',
        ]);

        $clerkUserData = new ClerkUserData(
            id: 'clerk-789',
            email: 'new@example.com',
            firstName: 'New',
            lastName: 'User',
        );

        $authService = Mockery::mock(ClerkAuthenticator::class);
        $authService->shouldReceive('authenticateToken')
            ->once()
            ->with('new-token')
            ->andReturn($clerkUserData);

        $provisioningService = Mockery::mock(CustomerProvisioner::class);
        $provisioningService->shouldReceive('provision')
            ->once()
            ->with(Mockery::type(ClerkUserData::class))
            ->andReturn($newUser);

        $loginService = Mockery::mock(CustomerLoginHandler::class);
        $loginService->shouldReceive('recordLogin')
            ->once()
            ->with(Mockery::type(User::class));

        $middleware = $this->createMiddleware($authService, $provisioningService, $loginService);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer new-token');

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('clerk-789', auth()->user()->clerk_user_id);
    }
}
