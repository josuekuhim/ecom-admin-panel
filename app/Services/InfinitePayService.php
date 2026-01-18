<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class InfinitePayService implements PaymentGateway
{
    protected $clientId;
    protected $clientSecret;
    protected $baseUri;
    protected $accessToken;
    protected int $timeout;
    protected int $retries;
    protected int $retrySleep;

    public function __construct()
    {
        $this->clientId = config('infinitepay.client_id');
        $this->clientSecret = config('infinitepay.client_secret');
        $this->baseUri = config('infinitepay.base_uri');
        $this->timeout = (int) config('infinitepay.timeout', 8);
        $this->retries = (int) config('infinitepay.retries', 1);
        $this->retrySleep = (int) config('infinitepay.retry_sleep', 200);
        $this->accessToken = $this->getAccessToken();
    }

    private function getAccessToken()
    {
        $response = Http::asForm()
            ->timeout($this->timeout)
            ->retry($this->retries, $this->retrySleep)
            ->post($this->baseUri . '/v2/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

        if ($response->failed()) {
            // Handle error appropriately
            return null;
        }

        return $response->json('access_token');
    }

    public function createCharge(Order $order, array $metadata = [])
    {
        $payload = [
            'valor' => $order->total_amount,
            'origem_transacao' => 'ECOMMERCE',
            'cliente' => [
                'nome' => $order->user->name,
                'email' => $order->user->email,
            ],
            'referencia' => (string) $order->id,
            'metadata' => array_merge([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ], $metadata),
        ];

        // Optionally include itemized lines (server-calculated) for reconciliation
        $order->loadMissing('items');
        $items = [];
        foreach ($order->items as $it) {
            $items[] = [
                'descricao' => optional($it->variant->product ?? null)->name ?? 'Produto',
                'quantidade' => (int) $it->quantity,
                'valor_unitario' => (float) $it->price,
            ];
        }
        if (!empty($items)) {
            $payload['itens'] = $items;
        }

        $headers = [];
        // Idempotency to avoid duplicated charges on retries (if the API supports it)
        $headers['Idempotency-Key'] = 'order-' . $order->id . '-' . (string) Str::uuid();

        $response = Http::withToken($this->accessToken)
            ->withHeaders($headers)
            ->timeout($this->timeout)
            ->retry($this->retries, $this->retrySleep)
            ->post($this->baseUri . '/v2/cobrancas', $payload);

        return $response->json();
    }
}
