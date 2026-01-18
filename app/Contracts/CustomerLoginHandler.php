<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

/**
 * Contract for customer login operations.
 */
interface CustomerLoginHandler
{
    /**
     * Record customer login and initialize session resources.
     */
    public function recordLogin(User $customer): void;
}
