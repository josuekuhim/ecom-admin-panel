<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\ClerkTokenClaims;
use App\Data\ClerkUserData;
use App\Exceptions\Domain\AuthenticationException;

/**
 * Contract for Clerk authentication operations.
 */
interface ClerkAuthenticator
{
    /**
     * Verify a bearer token and extract claims.
     *
     * @throws AuthenticationException
     */
    public function verifyToken(string $token): ClerkTokenClaims;

    /**
     * Fetch user data from Clerk by user ID.
     *
     * @throws AuthenticationException
     */
    public function fetchUser(string $clerkUserId): ClerkUserData;

    /**
     * Verify token and fetch user in one operation.
     *
     * @throws AuthenticationException
     */
    public function authenticateToken(string $token): ClerkUserData;
}
