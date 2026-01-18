<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CustomerProvisioner;
use App\Data\ClerkUserData;
use App\Enums\CustomerType;
use App\Exceptions\Domain\AuthenticationException;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Handles provisioning and synchronization of customers from Clerk.
 *
 * Single Responsibility: Customer creation and updates from Clerk data only.
 */
final class ClerkCustomerProvisioningService implements CustomerProvisioner
{
    private const DEFAULT_COUNTRY = 'BR';

    /**
     * Create or retrieve existing customer from Clerk data.
     *
     * @throws AuthenticationException
     */
    public function provision(ClerkUserData $clerkUser): User
    {
        $customer = User::where('clerk_user_id', $clerkUser->id)->first();

        if ($customer) {
            $this->synchronize($customer, $clerkUser);
            return $customer->fresh();
        }

        return $this->create($clerkUser);
    }

    /**
     * Create a new customer from Clerk user data.
     *
     * @throws AuthenticationException
     */
    public function create(ClerkUserData $clerkUser): User
    {
        if (!$clerkUser->hasValidEmail()) {
            throw AuthenticationException::invalidEmailAddress($clerkUser->id);
        }

        try {
            $customer = User::create([
                'clerk_user_id' => $clerkUser->id,
                'name' => $clerkUser->getFullName(),
                'email' => $clerkUser->email,
                'avatar' => $clerkUser->profileImageUrl,
                'email_verified_at' => now(),
                'customer_type' => CustomerType::default()->value,
                'marketing_emails' => true,
                'first_login_at' => now(),
                'last_login_at' => now(),
                'country' => self::DEFAULT_COUNTRY,
            ]);

            Log::info('ClerkCustomerProvisioningService: Customer created', [
                'customer_id' => $customer->id,
                'clerk_user_id' => $clerkUser->id,
            ]);

            return $customer;
        } catch (\Exception $e) {
            Log::error('ClerkCustomerProvisioningService: Creation failed', [
                'clerk_user_id' => $clerkUser->id,
                'error' => $e->getMessage(),
            ]);

            throw AuthenticationException::customerCreationFailed($clerkUser->id, $e->getMessage());
        }
    }

    /**
     * Synchronize existing customer with latest Clerk data.
     */
    public function synchronize(User $customer, ClerkUserData $clerkUser): void
    {
        $updates = $this->buildUpdateData($customer, $clerkUser);

        if (!empty($updates)) {
            $customer->update($updates);

            Log::info('ClerkCustomerProvisioningService: Customer synchronized', [
                'customer_id' => $customer->id,
                'clerk_user_id' => $clerkUser->id,
                'updated_fields' => array_keys($updates),
            ]);
        }
    }

    /**
     * Build array of fields that need updating.
     */
    private function buildUpdateData(User $customer, ClerkUserData $clerkUser): array
    {
        $updates = [];

        $name = $clerkUser->getFullName();
        if ($name !== $customer->name) {
            $updates['name'] = $name;
        }

        if ($clerkUser->email !== $customer->email) {
            $updates['email'] = $clerkUser->email;
            $updates['email_verified_at'] = now();
        }

        if ($clerkUser->profileImageUrl && $clerkUser->profileImageUrl !== $customer->avatar) {
            $updates['avatar'] = $clerkUser->profileImageUrl;
        }

        return $updates;
    }
}
