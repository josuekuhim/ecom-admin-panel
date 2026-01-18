<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Exceptions\Domain\OrderAlreadyProcessedException;
use App\Exceptions\Domain\OrderNotFoundException;
use App\Models\User;
use App\Services\OrderTotalService;
use App\Services\PaymentResponseNormalizer;
use App\Services\PaymentSignatureService;

/**
 * Action to process order checkout and initiate payment.
 */
final class CheckoutOrderAction
{
    public function __construct(
        private readonly PaymentGateway $paymentGateway,
        private readonly OrderTotalService $orderTotalService,
        private readonly PaymentSignatureService $signatureService,
        private readonly PaymentResponseNormalizer $responseNormalizer,
    ) {
    }

    /**
     * Execute the checkout process for an order.
     *
     * @param User $user The authenticated user
     * @param int $orderId The order to checkout
     * @return array<string, mixed> Normalized payment response
     * @throws OrderNotFoundException
     * @throws OrderAlreadyProcessedException
     */
    public function execute(User $user, int $orderId): array
    {
        $order = $user->orders()->where('orders.id', $orderId)->first();

        if (!$order) {
            throw new OrderNotFoundException('Order not found');
        }

        if ($order->status !== OrderStatus::Pending->value) {
            throw new OrderAlreadyProcessedException('Order already processed');
        }

        $this->orderTotalService->recalc($order);

        $signature = $this->signatureService->sign(
            (int) $order->id,
            (float) $order->total_amount,
            (int) $order->user_id
        );

        $paymentDetails = $this->paymentGateway->createCharge($order, [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'signature' => $signature,
        ]);

        if (!is_array($paymentDetails)) {
            $paymentDetails = [];
        }

        if (isset($paymentDetails['id'])) {
            $order->transaction_id = $paymentDetails['id'];
            $order->save();
        }

        return $this->responseNormalizer->normalize($order, $paymentDetails);
    }
}
