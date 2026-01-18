<?php

namespace App\Services;

class PaymentSignatureService
{
    public function sign(int $orderId, float $totalAmount, int $userId): string
    {
        $payload = $orderId . '|' . $totalAmount . '|' . $userId;
        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }
}
