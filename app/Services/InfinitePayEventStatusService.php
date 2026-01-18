<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class InfinitePayEventStatusService
{
    private array $paidEvents = [
        'transaction_paid',
        'transaction_authorized',
        'transaction_captured',
        'transaction_confirmed',
        'transaction_settled',
        'pix_paid',
        'payment_paid',
    ];

    private array $failedEvents = [
        'transaction_refused',
        'transaction_failed',
        'payment_failed',
    ];

    private array $canceledEvents = [
        'transaction_canceled',
        'payment_canceled',
    ];

    public function handle(Order $order, string $eventType): void
    {
        $finalizedStatuses = ['paid', 'failed', 'canceled'];
        $isFinalized = in_array($order->status, $finalizedStatuses, true);

        if (in_array($eventType, $this->paidEvents, true)) {
            $this->markIfNotFinalized($order, 'paid', $eventType, $isFinalized);
            return;
        }

        if (in_array($eventType, $this->failedEvents, true)) {
            $this->markIfNotFinalized($order, 'failed', $eventType, $isFinalized);
            return;
        }

        if (in_array($eventType, $this->canceledEvents, true)) {
            $this->markIfNotFinalized($order, 'canceled', $eventType, $isFinalized);
        }
    }

    private function markIfNotFinalized(Order $order, string $status, string $eventType, bool $isFinalized): void
    {
        if ($isFinalized) {
            return;
        }

        $order->status = $status;
        $order->save();

        Log::info("Order #{$order->id} status updated", [
            'status' => $status,
            'event' => $eventType,
        ]);
    }
}
