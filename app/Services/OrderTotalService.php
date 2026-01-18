<?php

namespace App\Services;

use App\Models\Order;

class OrderTotalService
{
    /**
     * Recalcula total do pedido a partir dos itens e frete.
     */
    public function recalc(Order $order): void
    {
        $order->loadMissing(['items', 'user']);

        $sum = 0.0;
        foreach ($order->items as $item) {
            $qty = (int) ($item->quantity ?? 0);
            $price = (float) ($item->price ?? 0);
            $sum += $qty * $price;
        }

        $shipping = (float) ($order->shipping_price ?? 0);
        $sum += $shipping;

        $order->total_amount = round($sum, 2);

        if (empty($order->status)) {
            $order->status = 'pending';
        }

        $order->save();
    }
}
