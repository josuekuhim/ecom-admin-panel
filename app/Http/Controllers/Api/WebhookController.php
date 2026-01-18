<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InfinitePayEventStatusService;
use App\Services\InfinitePaySignatureVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private InfinitePaySignatureVerifier $signatureVerifier,
        private InfinitePayEventStatusService $eventStatusService,
    ) {
    }

    public function handle(Request $request)
    {
        $payload = $request->all();
        $eventType = $payload['event_type'] ?? null;
        $transactionId = $payload['data']['transaction_id'] ?? null;

        if (!$this->signatureVerifier->verify($request->getContent(), $request->header('X-InfinitePay-Signature'))) {
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        if (!$eventType || !$transactionId) {
            Log::warning('InfinitePay webhook with missing data.', $payload);
            return response()->json(['status' => 'error', 'message' => 'Missing data'], 400);
        }

        Log::info('InfinitePay webhook received.', $payload);

        $order = Order::where('transaction_id', $transactionId)->first();

        if (!$order) {
            Log::warning("Order not found for transaction ID: {$transactionId}");
            return response()->json(['status' => 'error', 'message' => 'Order not found']);
        }

        $this->eventStatusService->handle($order, $eventType);

        return response()->json(['status' => 'received']);
    }

    /**
     * Local-only webhook simulator to mark an order as paid without a public URL.
     */
    public function simulate(Request $request)
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        $orderId = $request->input('order_id');
        $transactionId = $request->input('transaction_id');
        if (!$orderId && !$transactionId) {
            return response()->json(['status' => 'error', 'message' => 'Provide order_id or transaction_id'], 422);
        }

        $order = $orderId
            ? Order::find($orderId)
            : Order::where('transaction_id', $transactionId)->first();

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        if ($order->status !== 'paid') {
            $order->status = 'paid';
            $order->save();
        }

        return response()->json(['status' => 'ok', 'order_id' => $order->id, 'new_status' => $order->status]);
    }
}
