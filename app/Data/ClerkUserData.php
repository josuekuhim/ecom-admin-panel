<?php

declare(strict_types=1);

namespace App\Data;

/**
 * DTO representing a Clerk user with typed properties.
 */
final readonly class ClerkUserData
{
    public function __construct(
        public string $id,
        public string $email,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $profileImageUrl = null,
    ) {
    }

    public static function fromClerkResponse(object $clerkUser): self
    {
        $primaryEmail = collect($clerkUser->emailAddresses ?? [])
            ->firstWhere('id', $clerkUser->primaryEmailAddressId);

        $email = $primaryEmail->emailAddress ?? '';

        return new self(
            id: $clerkUser->id,
            email: $email,
            firstName: $clerkUser->firstName ?? null,
            lastName: $clerkUser->lastName ?? null,
            profileImageUrl: $clerkUser->profileImageUrl ?? null,
        );
    }

    public function getFullName(): string
    {
        $name = trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));

        return $name !== '' ? $name : $this->email;
    }

    public function hasValidEmail(): bool
    {
        return $this->email !== '' && filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
