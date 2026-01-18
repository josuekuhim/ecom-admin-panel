<?php

namespace Tests\Unit;

use App\Services\ClerkService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClerkServiceTest extends TestCase
{
    #[Test]
    public function it_fetches_user_when_clerk_api_succeeds(): void
    {
        config(['clerk.secret_key' => 'sk_test']);

        Http::fake([
            'https://api.clerk.com/v1/users/user_123' => Http::response(['id' => 'user_123', 'email' => 'a@b.com'], 200),
        ]);

        $service = new ClerkService();
        $user = $service->getUser('user_123');

        $this->assertNotNull($user);
        $this->assertSame('user_123', $user->id);

        Http::assertSent(function ($request) {
            $authHeader = $request->header('Authorization');
            $value = is_array($authHeader) ? ($authHeader[0] ?? '') : $authHeader;

            return $request->hasHeader('Authorization')
                && str_contains((string) $value, 'Bearer sk_test');
        });
    }

    #[Test]
    public function it_returns_null_when_secret_key_missing(): void
    {
        config(['clerk.secret_key' => null]);

        $service = new ClerkService();

        $this->assertNull($service->getUser('user_123'));
        $this->assertNull($service->verifyToken('token'));
    }

    #[Test]
    public function it_verifies_token_and_returns_payload(): void
    {
        config(['clerk.secret_key' => 'sk_test']);

        Http::fake([
            'https://api.clerk.com/v1/tokens/verify' => Http::response(['sub' => 'user_999'], 200),
        ]);

        $service = new ClerkService();
        $payload = $service->verifyToken('valid-token');

        $this->assertNotNull($payload);
        $this->assertSame('user_999', $payload->sub);
    }
}
