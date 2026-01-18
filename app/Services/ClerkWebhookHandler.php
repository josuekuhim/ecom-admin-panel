<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class ClerkWebhookHandler
{
    public function __construct(private CustomerService $customerService)
    {
    }

    public function dispatch(string $eventType, array $eventData): void
    {
        switch ($eventType) {
            case 'user.created':
            case 'user.updated':
                $this->handleUserUpsert($eventData);
                break;
            case 'user.deleted':
                $this->handleUserDeleted($eventData);
                break;
            case 'session.created':
                $this->handleSessionCreated($eventData);
                break;
            default:
                Log::info('Unhandled Clerk webhook event type: ' . $eventType);
        }
    }

    private function handleUserUpsert(array $data): void
    {
        $clerkUserId = $data['id'] ?? null;
        if (!$clerkUserId) {
            Log::warning('Clerk webhook missing user id for upsert');
            return;
        }

        $customer = User::where('clerk_user_id', $clerkUserId)->first();

        if ($customer) {
            $success = $this->customerService->updateCustomerFromClerk($customer, $clerkUserId);
            if ($success) {
                Log::info('Customer updated via webhook.', [
                    'customer_id' => $customer->id,
                    'clerk_user_id' => $clerkUserId,
                    'event' => 'user.updated'
                ]);
            } else {
                Log::warning('Failed to update customer via webhook.', [
                    'clerk_user_id' => $clerkUserId
                ]);
            }
            return;
        }

        $customer = $this->customerService->createCustomerFromClerk($clerkUserId);
        if ($customer) {
            Log::info('Customer created via webhook.', [
                'customer_id' => $customer->id,
                'clerk_user_id' => $clerkUserId,
                'event' => 'user.created'
            ]);
        } else {
            Log::error('Failed to create customer via webhook.', [
                'clerk_user_id' => $clerkUserId
            ]);
        }
    }

    private function handleUserDeleted(array $data): void
    {
        $clerkUserId = $data['id'] ?? null;
        if (!$clerkUserId) {
            Log::warning('Clerk webhook missing user id for delete');
            return;
        }

        $customer = User::where('clerk_user_id', $clerkUserId)->first();

        if ($customer) {
            $customerId = $customer->id;
            $customer->delete();
            Log::info('Customer deleted via webhook.', [
                'customer_id' => $customerId,
                'clerk_user_id' => $clerkUserId,
                'event' => 'user.deleted'
            ]);
        } else {
            Log::warning('Customer not found for deletion.', [
                'clerk_user_id' => $clerkUserId
            ]);
        }
    }

    private function handleSessionCreated(array $data): void
    {
        $clerkUserId = $data['user_id'] ?? null;
        if (!$clerkUserId) {
            Log::warning('Session created webhook missing user_id');
            return;
        }

        $customer = $this->customerService->createOrGetCustomerFromClerk($clerkUserId);
        if ($customer) {
            Log::info('Customer session handled via webhook.', [
                'customer_id' => $customer->id,
                'clerk_user_id' => $clerkUserId,
                'event' => 'session.created',
                'is_new_customer' => $customer->first_login_at && \Carbon\Carbon::parse($customer->first_login_at)->isToday()
            ]);
        } else {
            Log::error('Failed to handle customer session via webhook.', [
                'clerk_user_id' => $clerkUserId
            ]);
        }
    }
}
