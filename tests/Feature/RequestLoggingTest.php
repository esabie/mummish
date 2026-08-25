<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RequestLoggingTest extends TestCase
{
    public function test_validation_failures_are_logged(): void
    {
        Log::spy();

        $this->from('/login')->post('/login', [
            'email' => 'not-an-email',
            'password' => 'secret',
        ])->assertRedirect('/login');

        Log::shouldHaveReceived('log')
            ->withArgs(function (string $level, string $message, array $context = []) {
                return $level === 'warning'
                    && $message === '[Validation] Request failed validation.'
                    && isset($context['error_fields'])
                    && in_array('email', $context['error_fields'], true);
            })
            ->atLeast()
            ->once();
    }

    public function test_mutating_requests_are_logged_even_when_http_logging_is_off(): void
    {
        config(['logging.log_http_requests' => false]);

        Log::spy();

        $this->from('/')->post('/newsletter', [
            'email' => 'shopper@example.com',
        ]);

        Log::shouldHaveReceived('log')
            ->withArgs(function (string $level, string $message) {
                return $level === 'info' && $message === '[HTTP] Request completed.';
            })
            ->atLeast()
            ->once();
    }

    public function test_client_errors_include_input_summary(): void
    {
        Log::spy();

        $this->postJson('/login', [
            'email' => 'not-an-email',
            'password' => 'secret',
        ])->assertStatus(422);

        Log::shouldHaveReceived('log')
            ->withArgs(function (string $level, string $message, array $context = []) {
                return $level === 'warning'
                    && $message === '[HTTP] Request client error.'
                    && ($context['status'] ?? null) === 422
                    && isset($context['input_summary']['fields']['email']);
            })
            ->atLeast()
            ->once();
    }
}
