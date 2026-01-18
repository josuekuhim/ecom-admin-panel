<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ClerkClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Clerk API client implementation.
 *
 * Handles HTTP communication with Clerk's backend API.
 */
final class ClerkService implements ClerkClient
{
    private const BASE_URL = 'https://api.clerk.com/v1';

    private readonly string $secretKey;
    private readonly int $timeout;
    private readonly int $retries;
    private readonly int $retrySleep;

    public function __construct()
    {
        $this->secretKey = (string) config('clerk.secret_key', '');
        $this->timeout = (int) config('clerk.timeout', 5);
        $this->retries = (int) config('clerk.retries', 2);
        $this->retrySleep = (int) config('clerk.retry_sleep', 100);
    }

    public function getUser(string $clerkUserId): ?object
    {
        if ($clerkUserId === '' || $this->secretKey === '') {
            return null;
        }

        return $this->request('GET', "/users/{$clerkUserId}");
    }

    public function listUsers(array $params = []): ?object
    {
        if ($this->secretKey === '') {
            return null;
        }

        return $this->request('GET', '/users', $params);
    }

    public function verifyToken(string $token): ?object
    {
        if ($token === '' || $this->secretKey === '') {
            return null;
        }

        return $this->request('POST', '/tokens/verify', ['token' => $token]);
    }

    /**
     * Execute HTTP request to Clerk API.
     */
    private function request(string $method, string $endpoint, array $data = []): ?object
    {
        try {
            $http = Http::withHeaders([
                'Authorization' => "Bearer {$this->secretKey}",
                'Content-Type' => 'application/json',
            ])->timeout($this->timeout)
              ->retry($this->retries, $this->retrySleep);

            $url = self::BASE_URL . $endpoint;

            $response = match ($method) {
                'GET' => $http->get($url, $data),
                'POST' => $http->post($url, $data),
                default => $http->get($url),
            };

            if ($response->successful()) {
                return $response->object();
            }

            Log::error('ClerkService: API error', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('ClerkService: Request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
