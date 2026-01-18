<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ClerkClient;
use App\Data\ClerkUserData;
use App\Data\GoogleUserData;
use App\Data\ProfileData;
use App\Enums\CustomerType;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Facade service for customer operations.
 *
 * Coordinates between specialized services while maintaining backward compatibility.
 * New code should prefer using specialized services directly.
 *
 * @deprecated Use ClerkCustomerProvisioningService, CustomerLoginService, etc. directly.
 */
class CustomerService
{
    private const DEFAULT_COUNTRY = 'BR';

    public function __construct(
        private readonly ClerkClient $clerkClient,
        private readonly CustomerProfileService $profileService,
        private readonly CustomerStatsService $statsService,
        private readonly CustomerLoginService $loginService,
    ) {
    }

    /**
     * Create or get an existing customer using Clerk user id.
     */
    public function createOrGetCustomerFromClerk(string $clerkUserId): ?User
    {
        $customer = User::where('clerk_user_id', $clerkUserId)->first();

        if ($customer) {
            $this->updateCustomerFromClerk($customer, $clerkUserId);
            $this->loginService->recordLogin($customer);
            return $customer->fresh();
        }

        $customer = $this->createCustomerFromClerk($clerkUserId);

        if ($customer) {
            $this->loginService->recordLogin($customer);
        }

        return $customer;
    }

    /**
     * Create a new customer pulling data from Clerk.
     */
    public function createCustomerFromClerk(string $clerkUserId): ?User
    {
        $clerkUser = $this->clerkClient->getUser($clerkUserId);

        if (!$clerkUser) {
            Log::warning('CustomerService: Clerk user not found', ['clerk_user_id' => $clerkUserId]);
            return null;
        }

        $userData = ClerkUserData::fromClerkResponse($clerkUser);

        if (!$userData->hasValidEmail()) {
            Log::warning('CustomerService: Invalid email', ['clerk_user_id' => $clerkUserId]);
            return null;
        }

        try {
            return User::create([
                'clerk_user_id' => $clerkUserId,
                'name' => $userData->getFullName(),
                'email' => $userData->email,
                'avatar' => $userData->profileImageUrl,
                'email_verified_at' => now(),
                'customer_type' => CustomerType::default()->value,
                'marketing_emails' => true,
                'first_login_at' => now(),
                'last_login_at' => now(),
                'country' => self::DEFAULT_COUNTRY,
            ]);
        } catch (\Exception $e) {
            Log::error('CustomerService: Creation failed', [
                'clerk_user_id' => $clerkUserId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sync an existing customer with Clerk data.
     */
    public function updateCustomerFromClerk(User $customer, string $clerkUserId): bool
    {
        $clerkUser = $this->clerkClient->getUser($clerkUserId);

        if (!$clerkUser) {
            Log::warning('CustomerService: Clerk user not found for update', ['clerk_user_id' => $clerkUserId]);
            return false;
        }

        $userData = ClerkUserData::fromClerkResponse($clerkUser);
        $updateData = $this->buildClerkUpdateData($customer, $userData);

        if (!empty($updateData)) {
            $customer->update($updateData);
        }

        return true;
    }

    /**
     * Build update data comparing customer with Clerk data.
     */
    private function buildClerkUpdateData(User $customer, ClerkUserData $userData): array
    {
        $updates = [];

        $name = $userData->getFullName();
        if ($name !== $customer->name) {
            $updates['name'] = $name;
        }

        if ($userData->email !== $customer->email) {
            $updates['email'] = $userData->email;
            $updates['email_verified_at'] = now();
        }

        if ($userData->profileImageUrl && $userData->profileImageUrl !== $customer->avatar) {
            $updates['avatar'] = $userData->profileImageUrl;
        }

        return $updates;
    }

    /**
     * Get or create customer from user data (NextAuth integration)
     */
    public function getOrCreateCustomer(User $user): User
    {
        $this->loginService->recordLogin($user);
        return $user;
    }

    /**
     * Create or get existing customer from Google OAuth data.
     */
    public function createOrGetCustomerFromGoogle(GoogleUserData $googleData): ?User
    {
        $customer = User::where('google_id', $googleData->google_id)
            ->orWhere('email', $googleData->email)
            ->first();

        if ($customer) {
            $this->syncGoogleData($customer, $googleData);
            $this->loginService->recordLogin($customer);
            return $customer;
        }

        return $this->createCustomerFromGoogle($googleData);
    }

    /**
     * Sync existing customer with Google data.
     */
    private function syncGoogleData(User $customer, GoogleUserData $googleData): void
    {
        $updates = [];

        if (!$customer->google_id) {
            $updates['google_id'] = $googleData->google_id;
        }

        if ($customer->name !== $googleData->name) {
            $updates['name'] = $googleData->name;
        }

        if (!empty($googleData->image) && $customer->avatar !== $googleData->image) {
            $updates['avatar'] = $googleData->image;
        }

        if (!empty($updates)) {
            $customer->update($updates);
        }
    }

    /**
     * Create a new customer from Google OAuth data.
     */
    public function createCustomerFromGoogle(GoogleUserData $googleData): ?User
    {
        try {
            $customer = User::create([
                'google_id' => $googleData->google_id,
                'name' => $googleData->name,
                'email' => $googleData->email,
                'avatar' => $googleData->image,
                'email_verified_at' => now(),
                'customer_type' => CustomerType::default()->value,
                'marketing_emails' => true,
                'first_login_at' => now(),
                'last_login_at' => now(),
                'country' => self::DEFAULT_COUNTRY,
            ]);

            $this->loginService->recordLogin($customer);

            Log::info('CustomerService: Google customer created', [
                'customer_id' => $customer->id,
                'google_id' => $googleData->google_id,
            ]);

            return $customer;
        } catch (\Exception $e) {
            Log::error('CustomerService: Google creation failed', [
                'google_data' => $googleData->toArray(),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get customer statistics.
     */
    public function getCustomerStats(User $customer): array
    {
        return $this->statsService->stats($customer);
    }

    /**
     * Complete customer profile with additional data.
     */
    public function completeCustomerProfile(User $customer, ProfileData $profileData): bool
    {
        return $this->profileService->completeProfile($customer, $profileData);
    }

    /**
     * Transfer cart items from session to authenticated user.
     */
    public function transferSessionCartToUser(User $customer, ?string $sessionId = null): bool
    {
        if (!$sessionId) {
            return false;
        }

        try {
            $customer->getOrCreateCart();

            Log::info('CustomerService: Session cart transfer attempted', [
                'customer_id' => $customer->id,
                'session_id' => $sessionId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('CustomerService: Cart transfer failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
