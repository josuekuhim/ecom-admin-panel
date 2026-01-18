<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class InfinitePaySignatureVerifier
{
    public function verify(string $payload, ?string $providedSignature): bool
    {
        $secret = config('infinitepay.webhook_secret');
        if (empty($secret)) {
            Log::error('InfinitePay webhook secret not configured.');
            return false;
        }

        $calculated = hash_hmac('sha256', $payload, $secret);
        if (!$providedSignature || !hash_equals($providedSignature, $calculated)) {
            Log::warning('Invalid InfinitePay webhook signature.');
            return false;
        }

        return true;
    }
}
