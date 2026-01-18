<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Services\CustomerService;

/**
 * Action to authenticate a user via Clerk and return customer with stats.
 */
final class AuthenticateWithClerkAction
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {
    }

    /**
     * Execute Clerk authentication and return customer data.
     *
     * @param string $clerkUserId The Clerk user identifier
     * @return array{customer: User, stats: array<string, mixed>}|null
     */
    public function execute(string $clerkUserId): ?array
    {
        $customer = $this->customerService->createOrGetCustomerFromClerk($clerkUserId);

        if (!$customer) {
            return null;
        }

        $stats = $this->customerService->getCustomerStats($customer);

        return [
            'customer' => $customer,
            'stats' => $stats,
        ];
    }
}
