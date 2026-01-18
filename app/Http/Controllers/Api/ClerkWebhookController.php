<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ClerkWebhookHandler;
use App\Services\ClerkWebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClerkWebhookController extends Controller
{
    public function __construct(
        private ClerkWebhookVerifier $verifier,
        private ClerkWebhookHandler $handler,
    ) {
    }

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $headers = $request->headers->all();
        $secret = config('clerk.webhook_secret');

        if (empty($secret)) {
            Log::error('Clerk webhook secret is not configured.');
            return response()->json(['status' => 'error', 'message' => 'Webhook secret not configured.'], 500);
        }

        if (!$this->verifier->verify($payload, $headers, $secret)) {
            Log::error('Clerk webhook verification failed');
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        $data = json_decode($payload, true);
        $eventType = $data['type'] ?? null;
        $eventData = $data['data'] ?? null;

        if (!$eventType || !$eventData) {
            Log::error('Invalid webhook payload structure');
            return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
        }

        Log::info('Clerk webhook received.', ['type' => $eventType]);

        $this->handler->dispatch($eventType, $eventData);

        return response()->json(['status' => 'received']);
    }
}