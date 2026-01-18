<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Services\CustomerService;

/**
 * Action to verify a Clerk session and resolve the customer.
 */
final class VerifyClerkSessionAction
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {
    }

    /**
     * Verify Clerk session and return the associated customer.
     *
     * @param string $clerkUserId The Clerk user identifier
     * @return User|null The customer if found or created
     */
    public function execute(string $clerkUserId): ?User
    {
        return $this->customerService->createOrGetCustomerFromClerk($clerkUserId);
    }
}
