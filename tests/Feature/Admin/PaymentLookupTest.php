<?php

namespace Tests\Feature\Admin;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\HealthBooking;
use App\Models\HealthProfessional;
use App\Models\HealthProfessionalService;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_payment_lookup_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/payment-lookup')
            ->assertOk()
            ->assertSee('Payment lookup')
            ->assertSee('Find a payment');
    }

    public function test_non_admin_cannot_access_payment_lookup(): void
    {
        $vendor = User::factory()->create(['role' => UserRole::Vendor]);

        $this->actingAs($vendor)
            ->get('/admin/payment-lookup')
            ->assertForbidden();
    }

    public function test_lookup_finds_shop_order_and_health_booking(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Order::query()->create([
            'order_number' => 'MM20260825LOOK',
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Paid,
            'paystack_reference' => 'MM20260825LOOK',
            'customer_name' => 'Ama Mensah',
            'customer_email' => 'ama@example.com',
            'customer_phone' => '0241234567',
            'shipping_address_line1' => '1 Test Street',
            'shipping_city' => 'Accra',
            'shipping_region' => 'Greater Accra',
            'subtotal_cents' => 10000,
            'shipping_cents' => 1500,
            'discount_cents' => 0,
            'total_cents' => 11500,
            'currency' => 'GHS',
            'paid_at' => now(),
        ]);

        $booking = $this->createHealthBooking('HB-LOOK01');

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => 11500,
                    'currency' => 'GHS',
                    'reference' => 'MM20260825LOOK',
                    'id' => 999001,
                    'channel' => 'mobile_money',
                    'gateway_response' => 'Successful',
                    'paid_at' => now()->toIso8601String(),
                    'customer' => ['email' => 'ama@example.com'],
                    'metadata' => [],
                ],
            ], 200),
        ]);

        $service = app(\App\Services\PaymentLookupService::class);

        $orderResult = $service->lookup('MM20260825LOOK', true);
        $this->assertCount(1, $orderResult['matches']);
        $this->assertSame('shop_order', $orderResult['matches'][0]['type']);
        $this->assertSame('paid', $orderResult['matches'][0]['local_status']);
        $this->assertSame('success', $orderResult['paystack']['status']);

        $bookingResult = $service->lookup($booking->reference, false);
        $this->assertCount(1, $bookingResult['matches']);
        $this->assertSame('health_booking', $bookingResult['matches'][0]['type']);
        $this->assertSame('HB-LOOK01', $bookingResult['matches'][0]['reference']);
        $this->assertNull($bookingResult['paystack']);
    }

    public function test_lookup_returns_empty_when_nothing_matches(): void
    {
        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => false,
                'message' => 'Transaction reference not found',
            ], 404),
        ]);

        $result = app(\App\Services\PaymentLookupService::class)->lookup('DOES-NOT-EXIST', true);

        $this->assertSame([], $result['matches']);
        $this->assertNull($result['paystack']);
        $this->assertNotNull($result['paystack_error']);
    }

    private function createHealthBooking(string $reference): HealthBooking
    {
        $user = User::factory()->create([
            'role' => UserRole::HealthProfessional,
            'email' => 'provider-lookup-'.uniqid('', true).'@example.com',
        ]);

        $professional = HealthProfessional::create([
            'user_id' => $user->id,
            'name' => 'Dr Lookup',
            'slug' => 'dr-lookup-'.uniqid(),
            'title' => 'Pediatrician',
            'specialty' => 'Pediatrics',
            'about' => 'Test.',
            'location' => 'Accra',
            'phone' => '0240000000',
            'email' => $user->email,
            'visit_modes' => ['Virtual'],
            'is_active' => true,
            'approval_status' => HealthProfessionalApprovalStatus::Approved,
        ]);

        $service = HealthProfessionalService::create([
            'health_professional_id' => $professional->id,
            'name' => 'Consultation',
            'visit_mode' => 'Virtual',
            'price_cedis' => 200,
            'duration_minutes' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return HealthBooking::create([
            'reference' => $reference,
            'health_professional_id' => $professional->id,
            'health_professional_service_id' => $service->id,
            'patient_name' => 'Kofi Boateng',
            'patient_email' => 'kofi@example.com',
            'patient_phone' => '0249876543',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00',
            'visit_mode' => 'Virtual',
            'status' => 'pending',
            'payment_status' => 'paid',
            'paystack_reference' => $reference,
            'amount_cents' => 20000,
            'commission_cents' => 2000,
            'professional_payout_cents' => 18000,
            'paid_at' => now(),
        ]);
    }
}
