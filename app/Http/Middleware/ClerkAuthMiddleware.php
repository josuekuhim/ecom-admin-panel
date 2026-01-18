<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\ClerkAuthenticator;
use App\Contracts\CustomerLoginHandler;
use App\Contracts\CustomerProvisioner;
use App\Exceptions\Domain\AuthenticationException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for authenticating requests via Clerk bearer token.
 *
 * Responsibilities:
 * - Extract and validate bearer token
 * - Delegate authentication to ClerkAuthenticator
 * - Delegate provisioning to CustomerProvisioner
 * - Set authenticated user on request
 *
 * Does NOT handle: customer creation logic, profile sync details, cart management.
 */
final class ClerkAuthMiddleware
{
    public function __construct(
        private readonly ClerkAuthenticator $authService,
        private readonly CustomerProvisioner $provisioningService,
        private readonly CustomerLoginHandler $loginService,
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = $this->extractBearerToken($request);
            $clerkUser = $this->authService->authenticateToken($token);
            $customer = $this->provisioningService->provision($clerkUser);

            $this->loginService->recordLogin($customer);
            $this->setAuthenticatedUser($request, $customer);

            return $next($request);
        } catch (AuthenticationException $e) {
            Log::warning('ClerkAuthMiddleware: Authentication failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return $this->unauthorizedResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Extract bearer token from request.
     *
     * @throws AuthenticationException
     */
    private function extractBearerToken(Request $request): string
    {
        $token = $request->bearerToken();

        if (!$token) {
            throw AuthenticationException::missingToken();
        }

        return $token;
    }

    /**
     * Set the authenticated user on the request and auth guard.
     */
    private function setAuthenticatedUser(Request $request, $user): void
    {
        $request->setUserResolver(fn () => $user);
        auth()->setUser($user);
    }

    /**
     * Return a JSON error response.
     */
    private function unauthorizedResponse(string $message, int $code = 401): Response
    {
        $statusCode = in_array($code, [401, 403, 404, 422, 500]) ? $code : 401;

        return response()->json(['message' => $message], $statusCode);
    }
}
