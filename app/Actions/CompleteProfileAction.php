<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\ProfileData;
use App\Models\User;
use App\Services\CustomerService;

/**
 * Action to complete a customer's profile with additional data.
 */
final class CompleteProfileAction
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {
    }

    /**
     * Complete customer profile with the provided data.
     *
     * @param User $customer The customer to update
     * @param array<string, mixed> $payload Profile data
     * @return bool True if profile was updated successfully
     */
    public function execute(User $customer, array $payload): bool
    {
        $profileData = ProfileData::fromArray($payload);

        return $this->customerService->completeCustomerProfile($customer, $profileData);
    }
}
