<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AdminSetupLink
{
    public const CACHE_KEY = 'admin_setup_token';

    public const TTL_HOURS = 24;

    public static function generate(): string
    {
        $token = Str::random(64);

        Cache::put(self::CACHE_KEY, $token, now()->addHours(self::TTL_HOURS));

        return $token;
    }

    public static function url(string $token): string
    {
        return rtrim((string) config('app.url'), '/').'/admin-setup/'.$token;
    }

    public static function isValid(string $token): bool
    {
        $expected = Cache::get(self::CACHE_KEY);

        return is_string($expected)
            && $expected !== ''
            && hash_equals($expected, $token);
    }

    public static function invalidate(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
