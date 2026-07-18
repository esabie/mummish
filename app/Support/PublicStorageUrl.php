<?php

namespace App\Support;

class PublicStorageUrl
{
    public static function fromStored(?string $stored): ?string
    {
        if ($stored === null || trim($stored) === '') {
            return null;
        }

        $stored = trim($stored);

        if (preg_match('#^https?://#i', $stored)) {
            $path = parse_url($stored, PHP_URL_PATH);

            if (is_string($path) && str_starts_with($path, '/storage/')) {
                return self::forPublicPath(ltrim($path, '/storage/'));
            }

            return $stored;
        }

        if (str_starts_with($stored, '/storage/')) {
            return self::forPublicPath(ltrim($stored, '/storage/'));
        }

        return self::forPublicPath($stored);
    }

    public static function forPublicPath(string $path): string
    {
        return asset('storage/'.ltrim($path, '/'));
    }

    public static function toStoredPath(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('#^https?://#i', $value)) {
            $path = parse_url($value, PHP_URL_PATH);

            if (is_string($path) && str_starts_with($path, '/storage/')) {
                return ltrim($path, '/storage/');
            }

            return $value;
        }

        if (str_starts_with($value, '/storage/')) {
            return ltrim($value, '/storage/');
        }

        return ltrim($value, '/');
    }
}
