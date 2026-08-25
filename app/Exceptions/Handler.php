<?php

namespace App\Exceptions;

use App\Support\AppLog;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $exception) {
            if ($exception instanceof ValidationException || $exception instanceof PostTooLargeException) {
                return false;
            }

            if ($exception instanceof UniqueConstraintViolationException) {
                AppLog::error('[Database] Unique constraint violation.', [
                    'exception' => $exception::class,
                    'sqlstate' => $exception->errorInfo[0] ?? null,
                    'driver_code' => $exception->errorInfo[1] ?? null,
                    'driver_message' => $exception->errorInfo[2] ?? $exception->getMessage(),
                    'sql' => method_exists($exception, 'getSql') ? $exception->getSql() : null,
                    'bindings' => method_exists($exception, 'getBindings') ? $exception->getBindings() : null,
                    'hint' => 'A unique database index rejected this write. Often an email/phone/card already exists, including on a soft-deleted row.',
                ]);

                return false;
            }

            if ($exception instanceof QueryException) {
                AppLog::error('[Database] Query failed.', [
                    'exception' => $exception::class,
                    'sqlstate' => $exception->errorInfo[0] ?? null,
                    'driver_code' => $exception->errorInfo[1] ?? null,
                    'driver_message' => $exception->errorInfo[2] ?? $exception->getMessage(),
                    'sql' => $exception->getSql(),
                    'bindings' => $exception->getBindings(),
                    'exception_file' => $exception->getFile(),
                    'exception_line' => $exception->getLine(),
                ]);

                return false;
            }

            AppLog::exception('[Exception] Unhandled throwable reported.', $exception);
        });
    }

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): void
    {
        if ($e instanceof ValidationException) {
            AppLog::warning('[Validation] Request failed validation.', [
                'error_fields' => array_keys($e->errors()),
                'errors' => $e->errors(),
                'error_summary' => collect($e->errors())
                    ->map(fn (array $messages, string $field) => $field.': '.implode(' | ', $messages))
                    ->values()
                    ->all(),
            ]);
        }

        if ($e instanceof PostTooLargeException) {
            AppLog::error('[HTTP] Request body too large for PHP post_max_size.', [
                'content_length' => request()?->header('Content-Length'),
                'content_type' => request()?->header('Content-Type'),
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
            ]);
        }

        parent::report($e);
    }
}
