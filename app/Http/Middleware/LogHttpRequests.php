<?php

namespace App\Http\Middleware;

use App\Support\AppLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogHttpRequests
{
    /**
     * @var list<string>
     */
    private array $skipPathPrefixes = [
        'build/',
        'storage/',
        '_ignition/',
        'favicon.ico',
        'robots.txt',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        AppLog::setRequestId((string) Str::uuid());
        $startedAt = microtime(true);

        $response = $next($request);

        $this->logCompletion($request, $response, $startedAt);

        return $response;
    }

    private function logCompletion(Request $request, Response $response, float $startedAt): void
    {
        $status = $response->getStatusCode();
        $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
        $slowThreshold = (int) config('logging.slow_request_ms', 1000);
        $logAllRequests = (bool) config('logging.log_http_requests', false);

        $context = [
            'status' => $status,
            'duration_ms' => $durationMs,
        ];

        if ($status >= 500) {
            AppLog::error('[HTTP] Request failed.', $context);

            return;
        }

        if ($status >= 400) {
            AppLog::warning('[HTTP] Request client error.', $context);

            return;
        }

        if ($logAllRequests || $durationMs >= $slowThreshold) {
            AppLog::info('[HTTP] Request completed.', $context);
        }
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->skipPathPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
