<?php

namespace App\Services;

use App\Support\LogSanitizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaystackService
{
    public function initializeTransaction(
        string $email,
        int $amountCents,
        string $reference,
        string $callbackUrl,
        array $metadata = [],
    ): array {
        $secret = $this->secretKey();

        $payload = [
            'email' => $email,
            'amount' => $amountCents,
            'currency' => 'GHS',
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ];

        Log::info('Paystack: initializing transaction.', [
            'reference' => $reference,
            'amount_cents' => $amountCents,
            'currency' => 'GHS',
            'email_masked' => LogSanitizer::maskEmail($email),
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
            'api_url' => $this->baseUrl().'/transaction/initialize',
            'payload_keys' => array_keys($payload),
        ]);

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post($this->baseUrl().'/transaction/initialize', $payload);

        Log::debug('Paystack: initialize HTTP response.', [
            'reference' => $reference,
            'http_status' => $response->status(),
            'api_status' => $response->json('status'),
            'api_message' => $response->json('message'),
        ]);

        if (! $response->successful() || ! ($response->json('status') === true)) {
            Log::error('Paystack: initialize failed.', [
                'reference' => $reference,
                'http_status' => $response->status(),
                'api_status' => $response->json('status'),
                'api_message' => $response->json('message'),
                'body' => $response->json(),
            ]);

            throw new RuntimeException('Unable to start Paystack payment. Please try again.');
        }

        $data = $response->json('data');

        Log::info('Paystack: initialize succeeded.', [
            'reference' => $data['reference'] ?? $reference,
            'access_code_present' => ! empty($data['access_code']),
            'authorization_url_host' => parse_url((string) ($data['authorization_url'] ?? ''), PHP_URL_HOST),
        ]);

        return [
            'authorization_url' => (string) ($data['authorization_url'] ?? ''),
            'access_code' => (string) ($data['access_code'] ?? ''),
            'reference' => (string) ($data['reference'] ?? $reference),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array
    {
        $secret = $this->secretKey();
        $url = $this->baseUrl().'/transaction/verify/'.urlencode($reference);

        Log::info('Paystack: verifying transaction.', [
            'reference' => $reference,
            'api_url' => $url,
        ]);

        $response = Http::withToken($secret)
            ->acceptJson()
            ->get($url);

        Log::debug('Paystack: verify HTTP response.', [
            'reference' => $reference,
            'http_status' => $response->status(),
            'api_status' => $response->json('status'),
            'api_message' => $response->json('message'),
        ]);

        if (! $response->successful() || ! ($response->json('status') === true)) {
            Log::warning('Paystack: verify request failed.', [
                'reference' => $reference,
                'http_status' => $response->status(),
                'api_status' => $response->json('status'),
                'api_message' => $response->json('message'),
                'body' => $response->json(),
            ]);

            throw new RuntimeException('Unable to verify Paystack payment.');
        }

        $data = $response->json('data') ?? [];

        Log::info('Paystack: verify succeeded.', [
            'reference' => $reference,
            'status' => $data['status'] ?? null,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? null,
            'channel' => $data['channel'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
            'transaction_id' => $data['id'] ?? null,
            'customer_email_masked' => LogSanitizer::maskEmail(
                is_array($data['customer'] ?? null) ? ($data['customer']['email'] ?? null) : null
            ),
        ]);

        return $data;
    }

    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        if ($signature === null || $signature === '') {
            Log::warning('Paystack: webhook signature missing.', [
                'payload_length' => strlen($payload),
            ]);

            return false;
        }

        $secret = $this->secretKey();
        $computed = hash_hmac('sha512', $payload, $secret);
        $valid = hash_equals($computed, $signature);

        Log::debug('Paystack: webhook signature checked.', [
            'valid' => $valid,
            'payload_length' => strlen($payload),
            'signature_length' => strlen($signature),
        ]);

        return $valid;
    }

    public function publicKey(): string
    {
        return (string) config('services.paystack.public_key', '');
    }

    private function secretKey(): string
    {
        $secret = config('services.paystack.secret_key');

        if (! is_string($secret) || $secret === '') {
            Log::error('Paystack: secret key not configured.');

            throw new RuntimeException('Paystack is not configured.');
        }

        return $secret;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.paystack.payment_url', 'https://api.paystack.co'), '/');
    }
}
