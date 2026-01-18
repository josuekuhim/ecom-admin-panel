<?php

namespace App\Services;

use App\Models\User;

class CustomerStatsService
{
    public function stats(User $customer): array
    {
        return [
            'total_orders' => $customer->getOrdersCount(),
            'total_spent' => $customer->getTotalOrdersValue(),
            'has_complete_profile' => $customer->hasCompleteAddress(),
            'is_google_user' => !empty($customer->google_id),
            'first_login' => $customer->first_login_at?->diffForHumans(),
            'last_login' => $customer->last_login_at?->diffForHumans(),
            'cart_items_count' => $customer->cart?->items()->count() ?? 0,
        ];
    }
}
