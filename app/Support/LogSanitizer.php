<?php

namespace App\Support;

class LogSanitizer
{
    public static function maskEmail(?string $email): string
    {
        if ($email === null || $email === '' || ! str_contains($email, '@')) {
            return '***';
        }

        [$local, $domain] = explode('@', $email, 2);

        return substr($local, 0, 1).'***@'.$domain;
    }

    public static function maskPhone(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '***';
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) < 4) {
            return '***';
        }

        return substr($digits, 0, 3).'****'.substr($digits, -2);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function maskShipping(array $data): array
    {
        return [
            'customer_name' => self::maskName((string) ($data['customer_name'] ?? '')),
            'customer_email' => self::maskEmail(isset($data['customer_email']) ? (string) $data['customer_email'] : null),
            'customer_phone' => self::maskPhone(isset($data['customer_phone']) ? (string) $data['customer_phone'] : null),
            'shipping_city' => $data['shipping_city'] ?? null,
            'shipping_region' => $data['shipping_region'] ?? null,
            'shipping_address_line1' => self::maskAddress((string) ($data['shipping_address_line1'] ?? '')),
        ];
    }

    public static function maskName(string $name): string
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            return '***';
        }

        $parts = preg_split('/\s+/', $trimmed) ?: [];

        return collect($parts)
            ->map(fn (string $part) => substr($part, 0, 1).'***')
            ->implode(' ');
    }

    public static function maskAddress(string $address): string
    {
        $trimmed = trim($address);

        if ($trimmed === '') {
            return '***';
        }

        return substr($trimmed, 0, 6).'***';
    }
}
