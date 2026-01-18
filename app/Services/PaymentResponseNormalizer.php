<?php

namespace App\Services;

use App\Models\Order;

class PaymentResponseNormalizer
{
    /**
     * Normaliza campos retornados pelo gateway para o frontend.
     */
    public function normalize(Order $order, array $paymentDetails): array
    {
        $checkoutUrl = $paymentDetails['checkout_url']
            ?? $paymentDetails['url']
            ?? $paymentDetails['payment_url']
            ?? $paymentDetails['link']
            ?? $paymentDetails['link_de_pagamento']
            ?? null;

        $qrBase64 = $paymentDetails['qr_code_base64']
            ?? ($paymentDetails['pix']['qr_code_base64'] ?? null)
            ?? ($paymentDetails['qrcode_base64'] ?? null);

        $pixCopyPaste = $paymentDetails['pix_copy_paste']
            ?? ($paymentDetails['pix']['copy_paste'] ?? null)
            ?? ($paymentDetails['pix']['code'] ?? null);

        return [
            'id' => $paymentDetails['id'] ?? $order->transaction_id,
            'checkout_url' => $checkoutUrl,
            'qr_code_base64' => $qrBase64,
            'pix_copy_paste' => $pixCopyPaste,
            'status' => $paymentDetails['status'] ?? null,
            'raw' => $paymentDetails,
        ];
    }
}
