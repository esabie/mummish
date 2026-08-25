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
        'sitemap.xml',
    ];

    /**
     * @var list<string>
     */
    private array $mutatingMethods = [
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
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
        $isMutating = in_array($request->method(), $this->mutatingMethods, true);

        $context = [
            'status' => $status,
            'duration_ms' => $durationMs,
            'content_length' => $request->header('Content-Length'),
            'content_type' => $request->header('Content-Type'),
        ];

        if ($status >= 400 || $isMutating) {
            $context['input_summary'] = $this->summarizeInput($request);
            $context['query'] = $request->query();
        }

        if ($status === 422) {
            $context['validation_errors'] = $this->extractValidationErrors($response);
        }

        if ($status >= 500) {
            AppLog::error('[HTTP] Request failed.', $context);

            return;
        }

        if ($status >= 400) {
            AppLog::warning('[HTTP] Request client error.', $context);

            return;
        }

        if ($logAllRequests || $isMutating || $durationMs >= $slowThreshold) {
            AppLog::info('[HTTP] Request completed.', $context);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function summarizeInput(Request $request): array
    {
        $summary = [
            'fields' => [],
            'files' => [],
        ];

        foreach ($request->except([]) as $key => $value) {
            if ($request->hasFile($key)) {
                continue;
            }

            $summary['fields'][$key] = $this->summarizeField($value);
        }

        foreach ($request->allFiles() as $key => $file) {
            $files = is_array($file) ? $file : [$file];

            $summary['files'][$key] = array_values(array_map(function ($uploaded) {
                if (! is_object($uploaded) || ! method_exists($uploaded, 'getSize')) {
                    return ['present' => true];
                }

                return [
                    'name' => method_exists($uploaded, 'getClientOriginalName')
                        ? $uploaded->getClientOriginalName()
                        : null,
                    'size' => $uploaded->getSize(),
                    'mime' => method_exists($uploaded, 'getMimeType')
                        ? $uploaded->getMimeType()
                        : null,
                    'error' => method_exists($uploaded, 'getError')
                        ? $uploaded->getError()
                        : null,
                ];
            }, $files));
        }

        return $summary;
    }

    private function summarizeField(mixed $value): mixed
    {
        if (is_array($value)) {
            return [
                'type' => 'array',
                'count' => count($value),
                'keys' => array_slice(array_map('strval', array_keys($value)), 0, 20),
            ];
        }

        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return $value;
        }

        $string = is_scalar($value) ? (string) $value : '[unserializable]';

        return [
            'type' => 'string',
            'length' => mb_strlen($string),
            'preview' => mb_substr($string, 0, 80),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractValidationErrors(Response $response): ?array
    {
        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            return null;
        }

        if (isset($decoded['errors']) && is_array($decoded['errors'])) {
            return $decoded['errors'];
        }

        return null;
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
