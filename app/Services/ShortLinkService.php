<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ShortLinkService
{
    public const CACHE_PREFIX = 'short_link:';

    /**
     * Store a destination URL behind a short public path suitable for SMS.
     */
    public function create(string $targetUrl, int $ttlMinutes = 60): string
    {
        $code = $this->uniqueCode();

        Cache::put(self::CACHE_PREFIX.$code, $targetUrl, now()->addMinutes(max(1, $ttlMinutes)));

        return url('/r/'.$code);
    }

    public function resolve(string $code): ?string
    {
        $code = strtolower(trim($code));

        if ($code === '' || ! preg_match('/^[a-z0-9]{6,16}$/', $code)) {
            return null;
        }

        $target = Cache::get(self::CACHE_PREFIX.$code);

        return is_string($target) && $target !== '' ? $target : null;
    }

    private function uniqueCode(): string
    {
        do {
            $code = Str::lower(Str::random(8));
        } while (Cache::has(self::CACHE_PREFIX.$code));

        return $code;
    }
}
