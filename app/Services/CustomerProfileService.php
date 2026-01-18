<?php

namespace App\Services;

use App\Data\ProfileData;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CustomerProfileService
{
    public function completeProfile(User $customer, ProfileData $profileData): bool
    {
        try {
            $updateData = $profileData->toArray();

            if (!empty($updateData)) {
                $customer->update($updateData);
                
                Log::info('Customer profile completed', [
                    'customer_id' => $customer->id,
                    'updated_fields' => array_keys($updateData)
                ]);
                
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Error completing customer profile', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
