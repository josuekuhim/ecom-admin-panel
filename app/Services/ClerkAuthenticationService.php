<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ClerkAuthenticator;
use App\Contracts\ClerkClient;
use App\Data\ClerkTokenClaims;
use App\Data\ClerkUserData;
use App\Exceptions\Domain\AuthenticationException;
use Illuminate\Support\Facades\Log;

/**
 * Handles Clerk authentication operations: token verification and user resolution.
 *
 * Single Responsibility: Authentication logic only, no customer provisioning.
 */
final class ClerkAuthenticationService implements ClerkAuthenticator
{
    public function __construct(
        private readonly ClerkClient $clerkClient,
    ) {
    }

    /**
     * Verify a bearer token and extract claims.
     *
     * @throws AuthenticationException
     */
    public function verifyToken(string $token): ClerkTokenClaims
    {
        $verified = $this->clerkClient->verifyToken($token);

        if (!$verified) {
            throw AuthenticationException::invalidToken();
        }

        $claims = ClerkTokenClaims::fromVerifiedToken($verified);

        if (!$claims->isValid()) {
            throw AuthenticationException::userIdNotFound();
        }

        return $claims;
    }

    /**
     * Fetch user data from Clerk by user ID.
     *
     * @throws AuthenticationException
     */
    public function fetchUser(string $clerkUserId): ClerkUserData
    {
        $clerkUser = $this->clerkClient->getUser($clerkUserId);

        if (!$clerkUser) {
            Log::warning('ClerkAuthenticationService: User not found', [
                'clerk_user_id' => $clerkUserId,
            ]);

            throw AuthenticationException::clerkUserNotFound($clerkUserId);
        }

        return ClerkUserData::fromClerkResponse($clerkUser);
    }

    /**
     * Verify token and fetch user in one operation.
     *
     * @throws AuthenticationException
     */
    public function authenticateToken(string $token): ClerkUserData
    {
        $claims = $this->verifyToken($token);

        return $this->fetchUser($claims->userId);
    }
}