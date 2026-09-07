<?php

namespace Tests\Feature\Admin;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Enums\SmsAudience;
use App\Enums\SmsCampaignStatus;
use App\Enums\UserRole;
use App\Enums\VendorApplicationStatus;
use App\Filament\Pages\SendSms;
use App\Models\HealthProfessional;
use App\Models\NewsletterCustomer;
use App\Models\SmsCampaign;
use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SendSmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.mnotify.sms_api_key' => 'test-key',
            'services.mnotify.sender_id' => 'MUMMISH',
        ]);
    }

    public function test_admin_can_open_send_sms_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/send-sms')
            ->assertOk()
            ->assertSee('Send SMS')
            ->assertSee('Compose message')
            ->assertSee('Recent campaigns');
    }

    public function test_non_admin_cannot_access_send_sms_page(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)
            ->get('/admin/send-sms')
            ->assertForbidden();
    }

    public function test_admin_can_send_sms_to_newsletter_subscribers(): void
    {
        Http::fake([
            'api.mnotify.com/*' => Http::response([
                'status' => 'success',
                'code' => '2000',
                'summary' => ['_id' => 'camp-1'],
            ], 200),
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        NewsletterCustomer::query()->create([
            'name' => 'Ama',
            'phone' => '0241111111',
        ]);
        NewsletterCustomer::query()->create([
            'name' => 'Kofi',
            'phone' => '0242222222',
        ]);

        Livewire::actingAs($admin)
            ->test(SendSms::class)
            ->fillForm([
                'audience' => SmsAudience::Newsletter->value,
                'message' => 'Hello from Mummish — new arrivals this week.',
            ])
            ->call('send')
            ->assertNotified();

        $campaign = SmsCampaign::query()->first();

        $this->assertNotNull($campaign);
        $this->assertSame(SmsAudience::Newsletter, $campaign->audience);
        $this->assertSame(2, $campaign->recipient_count);
        $this->assertSame(2, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);
        $this->assertSame(SmsCampaignStatus::Completed, $campaign->status);
        $this->assertSame($admin->id, $campaign->created_by_user_id);

        Http::assertSentCount(2);
    }

    public function test_admin_can_send_sms_to_custom_numbers(): void
    {
        Http::fake([
            'api.mnotify.com/*' => Http::response([
                'status' => 'success',
                'code' => '2000',
            ], 200),
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(SendSms::class)
            ->fillForm([
                'audience' => SmsAudience::Custom->value,
                'custom_phones' => "0243333333\n0204444444",
                'message' => 'Ops test message from Mummish admin.',
            ])
            ->call('send')
            ->assertNotified();

        $campaign = SmsCampaign::query()->first();

        $this->assertNotNull($campaign);
        $this->assertSame(SmsAudience::Custom, $campaign->audience);
        $this->assertSame(2, $campaign->recipient_count);
        $this->assertSame(2, $campaign->sent_count);
        $this->assertSame(SmsCampaignStatus::Completed, $campaign->status);
    }

    public function test_send_sms_rejects_empty_custom_audience(): void
    {
        Http::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(SendSms::class)
            ->fillForm([
                'audience' => SmsAudience::Custom->value,
                'custom_phones' => '',
                'message' => 'Should not send.',
            ])
            ->call('send')
            ->assertHasFormErrors(['custom_phones']);

        $this->assertDatabaseCount('sms_campaigns', 0);
        Http::assertNothingSent();
    }

    public function test_send_sms_rejects_audience_with_no_recipients(): void
    {
        Http::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(SendSms::class)
            ->fillForm([
                'audience' => SmsAudience::Newsletter->value,
                'message' => 'Nobody on the list yet.',
            ])
            ->call('send')
            ->assertNotified();

        $this->assertDatabaseCount('sms_campaigns', 0);
        Http::assertNothingSent();
    }

    public function test_vendor_and_health_audience_use_approved_phones_only(): void
    {
        Http::fake([
            'api.mnotify.com/*' => Http::response([
                'status' => 'success',
                'code' => '2000',
            ], 200),
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $vendorUser = User::factory()->create(['role' => UserRole::Vendor]);

        VendorApplication::query()->create([
            'user_id' => $vendorUser->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'shop_name' => 'Little Knot',
            'business_email' => $vendorUser->email,
            'phone' => '0245555555',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Approved,
        ]);

        VendorApplication::query()->create([
            'user_id' => User::factory()->create(['role' => UserRole::Vendor])->id,
            'first_name' => 'Pending',
            'last_name' => 'Vendor',
            'shop_name' => 'Not Yet',
            'business_email' => 'pending-vendor@example.com',
            'phone' => '0246666666',
            'category' => 'toys_development',
            'terms_accepted' => true,
            'status' => VendorApplicationStatus::Pending,
        ]);

        $hpUser = User::factory()->create(['role' => UserRole::HealthProfessional]);

        HealthProfessional::query()->create([
            'user_id' => $hpUser->id,
            'name' => 'Dr Approved',
            'slug' => 'dr-approved-sms',
            'title' => 'Pediatrician',
            'specialty' => 'Pediatrics',
            'about' => 'Test',
            'location' => 'Accra',
            'phone' => '0247777777',
            'email' => $hpUser->email,
            'visit_modes' => ['Virtual'],
            'is_active' => true,
            'approval_status' => HealthProfessionalApprovalStatus::Approved,
        ]);

        HealthProfessional::query()->create([
            'user_id' => User::factory()->create(['role' => UserRole::HealthProfessional])->id,
            'name' => 'Dr Pending',
            'slug' => 'dr-pending-sms',
            'title' => 'Nurse',
            'specialty' => 'Midwifery',
            'about' => 'Test',
            'location' => 'Accra',
            'phone' => '0248888888',
            'email' => 'pending-hp@example.com',
            'visit_modes' => ['Virtual'],
            'is_active' => false,
            'approval_status' => HealthProfessionalApprovalStatus::Pending,
        ]);

        Livewire::actingAs($admin)
            ->test(SendSms::class)
            ->fillForm([
                'audience' => SmsAudience::Vendors->value,
                'message' => 'Vendor tip of the week from Mummish.',
            ])
            ->call('send')
            ->assertNotified();

        $this->assertSame(1, SmsCampaign::query()->where('audience', SmsAudience::Vendors)->value('sent_count'));

        Livewire::actingAs($admin)
            ->test(SendSms::class)
            ->fillForm([
                'audience' => SmsAudience::HealthProfessionals->value,
                'message' => 'Health pro update from Mummish.',
            ])
            ->call('send')
            ->assertNotified();

        $this->assertSame(1, SmsCampaign::query()->where('audience', SmsAudience::HealthProfessionals)->value('sent_count'));
    }
}
