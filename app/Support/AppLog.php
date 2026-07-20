<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AppLog
{
    private static ?string $requestId = null;
    private const MAX_STRING_LENGTH = 1000;
    private const MAX_ARRAY_ITEMS = 25;
    private const MAX_DEPTH = 4;

    /**
     * @var list<string>
     */
    private static array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'secret',
        'authorization',
        'paystack_secret_key',
        'x-paystack-signature',
    ];

    public static function setRequestId(string $requestId): void
    {
        self::$requestId = $requestId;
    }

    public static function requestId(): string
    {
        if (self::$requestId === null) {
            self::$requestId = (string) Str::uuid();
        }

        return self::$requestId;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function debug(string $message, array $context = []): void
    {
        self::write('debug', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function exception(string $message, Throwable $exception, array $context = []): void
    {
        self::error($message, array_merge($context, [
            'exception' => $exception::class,
            'exception_message' => self::limitString($exception->getMessage()),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function write(string $level, string $message, array $context): void
    {
        Log::log($level, $message, array_merge(self::baseContext(), self::sanitizeContext($context)));
    }

    /**
     * @return array<string, mixed>
     */
    private static function baseContext(): array
    {
        $context = [
            'request_id' => self::requestId(),
        ];

        if (! app()->runningInConsole() && app()->bound('request')) {
            $request = request();

            $context['http_method'] = $request->method();
            $context['http_path'] = $request->path();
            $context['route_name'] = $request->route()?->getName();
            $context['ip'] = $request->ip();
            $context['user_id'] = $request->user()?->id;
            $context['is_inertia'] = $request->header('X-Inertia') === 'true';
        }

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private static function sanitizeContext(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            if (self::isSensitiveKey((string) $key)) {
                $sanitized[$key] = '***';

                continue;
            }

            $sanitized[$key] = self::normalizeValue($value);
        }

        return $sanitized;
    }

    private static function normalizeValue(mixed $value, int $depth = 0): mixed
    {
        if ($depth >= self::MAX_DEPTH) {
            return '[truncated depth]';
        }

        if (is_array($value)) {
            $normalized = [];
            $count = 0;

            foreach ($value as $key => $item) {
                if ($count >= self::MAX_ARRAY_ITEMS) {
                    $normalized['...'] = sprintf('[truncated %d items]', count($value) - self::MAX_ARRAY_ITEMS);
                    break;
                }

                $normalized[$key] = self::normalizeValue($item, $depth + 1);
                $count++;
            }

            return $normalized;
        }

        if (is_object($value)) {
            if ($value instanceof Throwable) {
                return sprintf(
                    '%s: %s',
                    $value::class,
                    self::limitString($value->getMessage())
                );
            }

            return sprintf('[object %s]', $value::class);
        }

        if (is_string($value)) {
            return self::limitString($value);
        }

        return $value;
    }

    private static function limitString(string $value): string
    {
        if (mb_strlen($value) <= self::MAX_STRING_LENGTH) {
            return $value;
        }

        return mb_substr($value, 0, self::MAX_STRING_LENGTH).'...[truncated]';
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::$sensitiveKeys as $sensitiveKey) {
            if ($normalized === $sensitiveKey || str_contains($normalized, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}
