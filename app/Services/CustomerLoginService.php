<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CustomerLoginHandler;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Handles login-related side effects for customers.
 *
 * Single Responsibility: Login tracking and session initialization.
 */
final class CustomerLoginService implements CustomerLoginHandler
{
    /**
     * Record customer login and initialize session resources.
     */
    public function recordLogin(User $customer): void
    {
        $customer->updateLastLogin();
        $customer->getOrCreateCart();

        Log::info('CustomerLoginService: Login recorded', [
            'customer_id' => $customer->id,
            'cart_id' => $customer->cart?->id,
        ]);
    }
}
