<?php

namespace Tests\Unit;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Jobs\SendHealthProfessionalMissingPayoutDetailsSms;
use App\Models\HealthProfessional;
use App\Models\User;
use App\Services\MnotifySmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendHealthProfessionalMissingPayoutDetailsSmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_missing_payout_details_sms(): void
    {
        config([
            'app.name' => 'Mummish',
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'TEST',
        ]);

        Http::fake([
            'api.mnotify.com/*' => Http::response([
                'status' => 'success',
                'code' => '2000',
            ], 200),
        ]);

        $professional = $this->createProfessional();

        (new SendHealthProfessionalMissingPayoutDetailsSms($professional->id))
            ->handle(app(MnotifySmsService::class));

        Http::assertSent(function ($request) {
            $message = $request->data()['message'] ?? '';

            return str_contains($message, 'Hi Dr')
                && str_contains($message, 'payout details')
                && str_contains($message, 'Health Services')
                && str_contains($message, 'Dashboard → Payment details');
        });
    }

    public function test_skips_when_payment_details_already_saved(): void
    {
        config([
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'TEST',
        ]);

        Http::fake();

        $professional = $this->createProfessional([
            'payment_method' => 'bank',
            'bank_name' => 'GCB BANK',
            'bank_account_name' => 'Dr Test',
            'bank_account_number' => '123',
        ]);

        (new SendHealthProfessionalMissingPayoutDetailsSms($professional->id))
            ->handle(app(MnotifySmsService::class));

        Http::assertNothingSent();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createProfessional(array $overrides = []): HealthProfessional
    {
        $user = User::factory()->create([
            'email' => 'health-sms-'.uniqid('', true).'@example.com',
        ]);

        return HealthProfessional::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Dr Test Provider',
            'slug' => 'dr-test-provider-'.uniqid(),
            'title' => 'Pediatrician',
            'specialty' => 'Pediatrics',
            'about' => 'Test.',
            'location' => 'Accra',
            'phone' => '0240000000',
            'email' => $user->email,
            'visit_modes' => ['Virtual'],
            'is_active' => true,
            'approval_status' => HealthProfessionalApprovalStatus::Approved,
        ], $overrides));
    }
}
