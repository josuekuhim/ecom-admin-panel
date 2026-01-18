<?php

return [
    'client_id' => env('INFINITEPAY_CLIENT_ID'),
    'client_secret' => env('INFINITEPAY_CLIENT_SECRET'),
    'base_uri' => env('INFINITEPAY_BASE_URI', 'https://api.infinitepay.io'),
    // Optional: secret to validate webhooks (configure in InfinitePay dashboard)
    'webhook_secret' => env('INFINITEPAY_WEBHOOK_SECRET'),
    'timeout' => env('INFINITEPAY_HTTP_TIMEOUT', 8),
    'retries' => env('INFINITEPAY_HTTP_RETRIES', 1),
    'retry_sleep' => env('INFINITEPAY_HTTP_RETRY_SLEEP', 200),
];
