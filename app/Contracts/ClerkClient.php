<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract for Clerk API client operations.
 *
 * All methods return typed objects or null on failure.
 * Implementations should handle API errors internally and log appropriately.
 */
interface ClerkClient
{
    /**
     * Fetch a user by their Clerk user ID.
     *
     * @param string $clerkUserId The Clerk user identifier
     * @return object|null The raw Clerk user object, or null if not found
     */
    public function getUser(string $clerkUserId): ?object;

    /**
     * List users with optional filtering/pagination.
     *
     * @param array<string, mixed> $params Query parameters for filtering
     * @return object|null The paginated list response, or null on failure
     */
    public function listUsers(array $params = []): ?object;

    /**
     * Verify a JWT token and return its claims.
     *
     * @param string $token The bearer token to verify
     * @return object|null The verified token claims, or null if invalid
     */
    public function verifyToken(string $token): ?object;
}
