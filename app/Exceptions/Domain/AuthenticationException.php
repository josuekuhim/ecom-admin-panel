<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Exception;

class AuthenticationException extends Exception
{
    public static function missingToken(): self
    {
        return new self('Bearer token is required', 401);
    }

    public static function invalidToken(): self
    {
        return new self('Invalid or expired token', 401);
    }

    public static function userIdNotFound(): self
    {
        return new self('User identifier not found in token claims', 401);
    }

    public static function userResolutionFailed(): self
    {
        return new self('Unable to resolve authenticated user', 401);
    }

    public static function clerkUserNotFound(string $clerkUserId): self
    {
        return new self("Clerk user not found: {$clerkUserId}", 404);
    }

    public static function invalidEmailAddress(string $clerkUserId): self
    {
        return new self("Clerk user has no valid email address: {$clerkUserId}", 422);
    }

    public static function customerCreationFailed(string $clerkUserId, string $reason): self
    {
        return new self("Failed to create customer for Clerk user {$clerkUserId}: {$reason}", 500);
    }
}
