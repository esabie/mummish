<?php

namespace App\Support;

class SafeRedirect
{
    /**
     * Store a same-origin relative path as the post-auth intended URL.
     */
    public static function remember(?string $path): void
    {
        $path = self::sanitize($path);

        if ($path === null) {
            return;
        }

        session(['url.intended' => url($path)]);
    }

    public static function sanitize(?string $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $path = trim($path);

        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        return $path;
    }
}
