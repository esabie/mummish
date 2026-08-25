<?php

namespace Tests\Unit;

use App\Support\AppLog;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AppLogTest extends TestCase
{
    public function test_nested_sensitive_keys_are_redacted(): void
    {
        Log::spy();

        AppLog::info('Nested secret test.', [
            'input_summary' => [
                'fields' => [
                    'email' => ['type' => 'string', 'preview' => 'a@b.com'],
                    'password' => ['type' => 'string', 'preview' => 'super-secret'],
                ],
            ],
        ]);

        Log::shouldHaveReceived('log')
            ->withArgs(function (string $level, string $message, array $context) {
                return $level === 'info'
                    && $message === 'Nested secret test.'
                    && ($context['input_summary']['fields']['password'] ?? null) === '***'
                    && ($context['input_summary']['fields']['email']['preview'] ?? null) === 'a@b.com';
            })
            ->once();
    }
}
