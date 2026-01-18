<?php

return [
    'secret_key' => env('CLERK_SECRET_KEY'),
    'webhook_secret' => env('CLERK_WEBHOOK_SECRET'),
    'timeout' => env('CLERK_HTTP_TIMEOUT', 5),
    'retries' => env('CLERK_HTTP_RETRIES', 2),
    'retry_sleep' => env('CLERK_HTTP_RETRY_SLEEP', 100),
];
