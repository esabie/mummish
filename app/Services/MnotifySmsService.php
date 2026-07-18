<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MnotifySmsService
{
    /**
     * Send one SMS via mNotify BMS API v2 “Quick bulk SMS”.
     *
     * @see https://developer.mnotify.com/
     */
    public function send(string $to, string $message): bool
    {
        $key = config('services.mnotify.sms_api_key');
        $senderId = config('services.mnotify.sender_id');
        $endpoint = config('services.mnotify.endpoint', 'https://api.mnotify.com/api/sms/quick');

        Log::info('Mnotify SMS (API v2 quick): send requested.', [
            'to_raw_masked' => self::maskPhoneForLog($to),
            'message_length' => strlen($message),
            'message_preview' => self::messagePreviewForLog($message),
            'endpoint_host' => parse_url((string) $endpoint, PHP_URL_HOST) ?: $endpoint,
        ]);

        if (! is_string($key) || $key === '' || ! is_string($senderId) || $senderId === '') {
            Log::warning('Mnotify SMS skipped: missing API key or sender ID.', [
                'has_api_key' => is_string($key) && $key !== '',
                'has_sender_id' => is_string($senderId) && $senderId !== '',
            ]);

            return false;
        }

        $recipient = self::formatRecipientForQuickApi($to);
        if ($recipient === '') {
            Log::warning('Mnotify SMS skipped: empty or invalid phone after normalization.', [
                'to_raw_masked' => self::maskPhoneForLog($to),
            ]);

            return false;
        }

        Log::debug('Mnotify SMS: recipient formatted for quick API.', [
            'recipient_masked' => self::maskPhoneForLog($recipient),
            'sender' => $senderId,
        ]);

        $url = self::appendQueryKey((string) $endpoint, $key);

        $payload = [
            'recipient' => [$recipient],
            'sender' => $senderId,
            'message' => $message,
            'is_schedule' => false,
            'schedule_date' => '',
        ];

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);
        } catch (Throwable $e) {
            Log::error('Mnotify SMS: HTTP request failed.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'recipient_masked' => self::maskPhoneForLog($recipient),
            ]);

            return false;
        }

        Log::debug('Mnotify SMS: HTTP response received.', [
            'status' => $response->status(),
            'recipient_masked' => self::maskPhoneForLog($recipient),
        ]);

        if (! $response->successful()) {
            Log::warning('Mnotify SMS HTTP error.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'recipient_masked' => self::maskPhoneForLog($recipient),
                'message_preview' => self::messagePreviewForLog($message),
            ]);

            return false;
        }

        $data = $response->json();
        if (self::isMnotifySuccess($data)) {
            Log::info('Mnotify SMS: send accepted by API.', [
                'recipient_masked' => self::maskPhoneForLog($recipient),
                'api_code' => is_array($data) ? ($data['code'] ?? null) : null,
                'api_status' => is_array($data) ? ($data['status'] ?? null) : null,
                'campaign_id' => is_array($data) ? data_get($data, 'summary._id') : null,
            ]);

            return true;
        }

        Log::warning('Mnotify SMS API reported failure.', [
            'response' => $data,
            'recipient_masked' => self::maskPhoneForLog($recipient),
            'message_preview' => self::messagePreviewForLog($message),
        ]);

        return false;
    }

    /**
     * Append ?key= or &key= to the endpoint URL (BMS API v2 convention).
     */
    public static function appendQueryKey(string $endpoint, string $key): string
    {
        $sep = str_contains($endpoint, '?') ? '&' : '?';

        return $endpoint.$sep.'key='.rawurlencode($key);
    }

    /**
     * Quick SMS examples use local Ghana MSISDN (e.g. 0241234567). Derive from normalized international form.
     */
    public static function formatRecipientForQuickApi(string $raw): string
    {
        $international = self::normalizeToInternationalDigits($raw);
        if ($international === '') {
            return '';
        }

        if (str_starts_with($international, '233') && strlen($international) > 3) {
            return '0'.substr($international, 3);
        }

        return $international;
    }

    /**
     * Mask phone for logs (never log full numbers).
     */
    public static function maskPhoneForLog(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return '(empty)';
        }

        if (strlen($digits) <= 6) {
            return '***';
        }

        return substr($digits, 0, 3).'***'.substr($digits, -4);
    }

    /**
     * BMS API v2 success responses include JSON "code" "2000" (and typically "status" "success").
     *
     * @param  array<string, mixed>|null  $data
     */
    public static function isMnotifySuccess(?array $data): bool
    {
        if ($data === null) {
            return false;
        }

        $code = isset($data['code']) ? (string) $data['code'] : '';

        if (! in_array($code, ['2000', '1000'], true)) {
            return false;
        }

        if (! isset($data['status']) || ! is_string($data['status'])) {
            return true;
        }

        return strcasecmp($data['status'], 'success') === 0;
    }

    public static function normalizeToInternationalDigits(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '233')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '233'.substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '233'.$digits;
        }

        return $digits;
    }

    public static function messagePreviewForLog(string $message, int $max = 240): string
    {
        $singleLine = preg_replace('/\s+/', ' ', trim($message)) ?? '';

        if ($singleLine === '') {
            return '(empty)';
        }

        if (strlen($singleLine) <= $max) {
            return $singleLine;
        }

        return substr($singleLine, 0, $max).'…';
    }
}
