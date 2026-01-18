<?php

declare(strict_types=1);

namespace App\Data;

/**
 * DTO representing verified Clerk token claims.
 */
final readonly class ClerkTokenClaims
{
    public function __construct(
        public string $userId,
        public ?int $exp = null,
        public ?int $iat = null,
    ) {
    }

    public static function fromVerifiedToken(object $tokenData): self
    {
        $userId = $tokenData->sub ?? $tokenData->user_id ?? '';

        return new self(
            userId: $userId,
            exp: isset($tokenData->exp) ? (int) $tokenData->exp : null,
            iat: isset($tokenData->iat) ? (int) $tokenData->iat : null,
        );
    }

    public function isValid(): bool
    {
        return $this->userId !== '';
    }

    public function isExpired(): bool
    {
        if ($this->exp === null) {
            return false;
        }

        return $this->exp < time();
    }
}
