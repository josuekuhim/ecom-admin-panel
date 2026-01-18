<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\ClerkUserData;
use App\Exceptions\Domain\AuthenticationException;
use App\Models\User;

/**
 * Contract for customer provisioning from Clerk.
 */
interface CustomerProvisioner
{
    /**
     * Create or retrieve existing customer from Clerk data.
     *
     * @throws AuthenticationException
     */
    public function provision(ClerkUserData $clerkUser): User;
}
