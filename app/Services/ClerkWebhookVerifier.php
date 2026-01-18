<?php

namespace App\Services;

use Svix\Webhook;
use Svix\Exception\WebhookVerificationException;

class ClerkWebhookVerifier
{
    /**
     * Verifica assinatura do webhook Clerk usando Svix.
     */
    public function verify(string $payload, array $headers, string $secret): bool
    {
        // Normaliza headers (Laravel fornece arrays)
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[$key] = is_array($value) ? ($value[0] ?? '') : $value;
        }

        try {
            $webhook = new Webhook($secret);
            $webhook->verify($payload, $normalized);
            return true;
        } catch (WebhookVerificationException $e) {
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
