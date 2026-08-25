<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHealthProfessionalRequest;
use App\Http\Requests\UpdateHealthProfessionalPayoutDetailsRequest;
use App\Jobs\SendHealthBookingCancelledSms;
use App\Jobs\SendHealthBookingConfirmedSms;
use App\Jobs\SendHealthBookingReviewInviteSms;
use App\Models\HealthBooking;
use App\Models\HealthProfessional;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HealthProfessionalDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $professional = $this->ownedProfessional($request);
        $professional->loadCount(['services', 'availability']);

        $today = now()->toDateString();
        $weekEnd = now()->addDays(6)->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $todaysBookings = $professional->bookings()
            ->with(['service:id,name'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('appointment_date', $today)
            ->orderBy('appointment_time')
            ->get()
            ->map(fn (HealthBooking $booking) => $this->bookingPayload($booking))
            ->values()
            ->all();

        $pendingBookings = $professional->bookings()
            ->with(['service:id,name'])
            ->where('status', 'pending')
            ->whereDate('appointment_date', '>=', $today)
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->limit(20)
            ->get()
            ->map(fn (HealthBooking $booking) => $this->bookingPayload($booking))
            ->values()
            ->all();

        $nextAppointment = $professional->bookings()
            ->with(['service:id,name'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($today) {
                $query->whereDate('appointment_date', '>', $today)
                    ->orWhere(function ($q) use ($today) {
                        $q->whereDate('appointment_date', $today)
                            ->whereTime('appointment_time', '>=', now()->format('H:i:s'));
                    });
            })
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->first();

        $stats = [
            'pending_count' => $professional->bookings()
                ->where('status', 'pending')
                ->whereDate('appointment_date', '>=', $today)
                ->count(),
            'today_confirmed_count' => $professional->bookings()
                ->where('status', 'confirmed')
                ->whereDate('appointment_date', $today)
                ->count(),
            'upcoming_confirmed_count' => $professional->bookings()
                ->where('status', 'confirmed')
                ->whereDate('appointment_date', '>=', $today)
                ->whereDate('appointment_date', '<=', $weekEnd)
                ->count(),
            'completed_month_count' => $professional->bookings()
                ->where('status', 'completed')
                ->whereDate('appointment_date', '>=', $monthStart)
                ->count(),
        ];

        return Inertia::render('HealthServices/Dashboard', [
            'professional' => $this->professionalSummary($professional),
            'payoutDetails' => [
                'payment_method' => $professional->payment_method,
                'payment_method_label' => $professional->paymentMethodLabel(),
                'bank_name' => $professional->bank_name,
                'bank_account_name' => $professional->bank_account_name,
                'bank_account_number' => $professional->bank_account_number,
                'mobile_money_provider' => $professional->mobile_money_provider,
                'mobile_money_name' => $professional->mobile_money_name,
                'mobile_money_number' => $professional->mobile_money_number,
            ],
            'ghanaBanks' => config('ghana_banks.names', []),
            'stats' => $stats,
            'next_appointment' => $nextAppointment
                ? $this->bookingPayload($nextAppointment)
                : null,
            'todays_bookings' => $todaysBookings,
            'pending_bookings' => $pendingBookings,
            'today_label' => now()->format('l, j F Y'),
            'greeting' => $this->greeting(),
        ]);
    }

    public function updatePayoutDetails(UpdateHealthProfessionalPayoutDetailsRequest $request): RedirectResponse
    {
        $professional = $this->ownedProfessional($request);

        if ($professional->hasPaymentDetails()) {
            return back()->with('error', 'Payment details are locked. Contact Mummish support if you need them changed.');
        }

        $professional->applyPayoutDetails($request->validated());

        return back()->with('success', 'Payment details saved. Contact Mummish support if you need them changed later.');
    }

    public function schedule(Request $request): Response
    {
        $professional = $this->ownedProfessional($request);
        $today = now()->toDateString();
        $rangeStart = now()->startOfMonth()->subMonth()->toDateString();
        $rangeEnd = now()->endOfMonth()->addMonths(2)->toDateString();

        $bookings = $professional->bookings()
            ->with(['service:id,name'])
            ->whereIn('status', ['pending', 'confirmed', 'cancelled', 'completed'])
            ->whereDate('appointment_date', '>=', $rangeStart)
            ->whereDate('appointment_date', '<=', $rangeEnd)
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->limit(200)
            ->get()
            ->map(fn (HealthBooking $booking) => $this->bookingPayload($booking))
            ->values()
            ->all();

        $pendingBookings = collect($bookings)
            ->filter(fn (array $booking) => $booking['status'] === 'pending'
                && $booking['appointment_date'] >= $today)
            ->values()
            ->all();

        $upcoming = collect($bookings)
            ->filter(fn (array $booking) => in_array($booking['status'], ['pending', 'confirmed'], true)
                && $booking['appointment_date'] >= $today)
            ->values()
            ->all();

        $recent = collect($bookings)
            ->filter(fn (array $booking) => in_array($booking['status'], ['cancelled', 'completed'], true)
                && $booking['appointment_date'] >= now()->subDays(14)->toDateString())
            ->sortBy([
                ['appointment_date', 'desc'],
                ['appointment_time', 'desc'],
            ])
            ->values()
            ->all();

        return Inertia::render('HealthServices/Schedule', [
            'professional' => $this->professionalSummary($professional),
            'bookings' => $bookings,
            'pending_bookings' => $pendingBookings,
            'upcoming' => $upcoming,
            'recent' => $recent,
            'today' => $today,
        ]);
    }

    public function confirmBooking(
        Request $request,
        HealthProfessional $professional,
        HealthBooking $booking,
    ): RedirectResponse {
        $this->ensureOwnsBooking($request, $professional, $booking);

        if ($booking->status === 'confirmed') {
            return back()->with('success', 'This booking is already confirmed.');
        }

        if ($booking->status !== 'pending') {
            return back()->with('error', 'Only pending booking requests can be confirmed.');
        }

        if (! $booking->isPaid()) {
            return back()->with('error', 'This booking has not been paid yet.');
        }

        $booking->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        SendHealthBookingConfirmedSms::dispatch($booking->id);

        return back()->with(
            'success',
            "Booking {$booking->reference} confirmed. The patient has been notified by SMS."
        );
    }

    public function declineBooking(
        Request $request,
        HealthProfessional $professional,
        HealthBooking $booking,
    ): RedirectResponse {
        $this->ensureOwnsBooking($request, $professional, $booking);

        if ($booking->status !== 'pending') {
            return back()->with('error', 'Only pending booking requests can be declined.');
        }

        $reason = $this->validatedCancellationReason($request);

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        SendHealthBookingCancelledSms::dispatch($booking->id);

        return back()->with(
            'success',
            "Booking {$booking->reference} declined. The patient has been notified by SMS."
        );
    }

    public function cancelBooking(
        Request $request,
        HealthProfessional $professional,
        HealthBooking $booking,
    ): RedirectResponse {
        $this->ensureOwnsBooking($request, $professional, $booking);

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Only confirmed bookings can be cancelled.');
        }

        $reason = $this->validatedCancellationReason($request);

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        SendHealthBookingCancelledSms::dispatch($booking->id);

        return back()->with(
            'success',
            "Booking {$booking->reference} cancelled. The patient has been notified by SMS."
        );
    }

    public function completeBooking(
        Request $request,
        HealthProfessional $professional,
        HealthBooking $booking,
    ): RedirectResponse {
        $this->ensureOwnsBooking($request, $professional, $booking);

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Only confirmed bookings can be marked completed.');
        }

        $booking->update([
            'status' => 'completed',
        ]);

        SendHealthBookingReviewInviteSms::dispatch($booking->id);

        return back()->with('success', "Booking {$booking->reference} marked as completed.");
    }

    public function edit(Request $request, HealthProfessional $professional): Response
    {
        $this->ensureOwns($request, $professional);

        $professional->load([
            'services' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            'availability' => fn ($q) => $q->orderBy('day_of_week')->orderBy('start_time'),
        ]);

        $experience = HealthProfessional::parseExperience($professional->experience);

        return Inertia::render('HealthServices/Edit', [
            'professional' => [
                'id' => $professional->id,
                'name' => $professional->name,
                'slug' => $professional->slug,
                'title' => $professional->title,
                'specialty' => $professional->specialty,
                'about' => $professional->about,
                'location' => $professional->location,
                'phone' => $professional->phone,
                'email' => $professional->email,
                'experience_amount' => $experience['amount'],
                'experience_unit' => $experience['unit'],
                'booking_note' => $professional->booking_note,
                'visit_modes' => $professional->visit_modes ?? [],
                'languages' => $professional->languages ?? [''],
                'highlights' => $professional->highlights ?? [''],
                'is_active' => $professional->is_active,
                'approval_status' => $professional->approval_status?->value ?? 'pending',
                'rejection_reason' => $professional->rejection_reason,
                'can_go_live' => $professional->isApproved(),
                'image_url' => $professional->image_path
                    ? asset('storage/'.$professional->image_path)
                    : $professional->image_url,
                'services' => $professional->services->map(fn ($service) => [
                    'name' => $service->name,
                    'visit_mode' => $service->visit_mode,
                    'price_cedis' => $service->price_cedis,
                    'duration_minutes' => $service->duration_minutes,
                ])->values()->all(),
                'availability' => $professional->availability->map(fn ($slot) => [
                    'day_of_week' => $slot->day_of_week,
                    'start_time' => substr((string) $slot->start_time, 0, 5),
                    'end_time' => substr((string) $slot->end_time, 0, 5),
                ])->values()->all(),
            ],
        ]);
    }

    public function update(StoreHealthProfessionalRequest $request, HealthProfessional $professional): RedirectResponse
    {
        $this->ensureOwns($request, $professional);

        $data = $request->validated();

        foreach ($data['availability'] as $window) {
            if ($window['end_time'] <= $window['start_time']) {
                return back()
                    ->withErrors(['availability' => 'Each availability end time must be after start time.'])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($request, $data, $professional) {
            $imagePath = $professional->image_path;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('health-professionals', 'public');
            }

            $professional->update([
                'name' => $data['name'],
                'title' => $data['title'],
                'specialty' => $data['specialty'],
                'about' => $data['about'],
                'location' => $data['location'],
                'phone' => $data['phone'],
                'email' => strtolower(trim($data['email'])),
                'visit_modes' => array_values(array_unique($data['visit_modes'])),
                'languages' => collect($data['languages'] ?? [])->filter()->values()->all(),
                'highlights' => collect($data['highlights'] ?? [])->filter()->values()->all(),
                'booking_note' => $data['booking_note'] ?? null,
                'experience' => HealthProfessional::formatExperience(
                    (int) $data['experience_amount'],
                    (string) $data['experience_unit'],
                ),
                'image_path' => $imagePath,
                'is_active' => $professional->isApproved()
                    ? (bool) ($data['is_active'] ?? $request->boolean('is_active'))
                    : false,
            ]);

            $request->user()?->update([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => strtolower(trim($data['email'])),
            ]);

            $professional->services()->delete();
            $professional->services()->createMany(
                collect($data['services'])
                    ->filter(fn (array $service) => trim((string) $service['name']) !== '')
                    ->values()
                    ->map(fn (array $service, int $index) => [
                        'name' => $service['name'],
                        'visit_mode' => $service['visit_mode'],
                        'price_cedis' => (int) $service['price_cedis'],
                        'duration_minutes' => (int) ($service['duration_minutes'] ?? 30),
                        'sort_order' => $index,
                        'is_active' => true,
                    ])->all()
            );

            $professional->availability()->delete();
            $professional->availability()->createMany(
                collect($data['availability'])
                    ->values()
                    ->map(fn (array $window) => [
                        'day_of_week' => (int) $window['day_of_week'],
                        'start_time' => $window['start_time'],
                        'end_time' => $window['end_time'],
                        'is_active' => true,
                    ])->all()
            );
        });

        return redirect()
            ->route('health-professionals.dashboard')
            ->with('success', 'Professional profile updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function professionalSummary(HealthProfessional $professional): array
    {
        return [
            'id' => $professional->id,
            'name' => $professional->name,
            'slug' => $professional->slug,
            'title' => $professional->title,
            'specialty' => $professional->specialty,
            'visit_modes' => $professional->visit_modes ?? [],
            'is_active' => $professional->is_active,
            'approval_status' => $professional->approval_status?->value ?? 'pending',
            'rejection_reason' => $professional->rejection_reason,
            'can_go_live' => $professional->isApproved(),
            'is_publicly_visible' => $professional->isPubliclyVisible(),
            'image_url' => $professional->image_path
                ? asset('storage/'.$professional->image_path)
                : $professional->image_url,
            'services_count' => $professional->services_count ?? $professional->services()->count(),
            'availability_count' => $professional->availability_count ?? $professional->availability()->count(),
            'has_payment_details' => $professional->hasPaymentDetails(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingPayload(HealthBooking $booking): array
    {
        $date = $booking->appointment_date instanceof Carbon
            ? $booking->appointment_date->copy()
            : Carbon::parse((string) $booking->appointment_date);

        $timeLabel = Carbon::parse((string) $booking->appointment_time)->format('g:i A');

        return [
            'id' => $booking->id,
            'reference' => $booking->reference,
            'status' => $booking->status,
            'patient_name' => $booking->patient_name,
            'patient_email' => $booking->patient_email,
            'patient_phone' => $booking->patient_phone,
            'visit_mode' => $booking->visit_mode,
            'service_name' => $booking->service?->name,
            'appointment_date' => $date->toDateString(),
            'appointment_date_label' => $date->format('D, j M Y'),
            'appointment_time' => $timeLabel,
            'notes' => $booking->notes,
            'cancellation_reason' => $booking->cancellation_reason,
            'can_confirm' => $booking->status === 'pending',
            'can_decline' => $booking->status === 'pending',
            'can_cancel' => $booking->status === 'confirmed',
            'can_complete' => $booking->status === 'confirmed',
        ];
    }

    private function greeting(): string
    {
        $hour = (int) now()->format('G');

        if ($hour < 12) {
            return 'Good morning';
        }

        if ($hour < 17) {
            return 'Good afternoon';
        }

        return 'Good evening';
    }

    private function validatedCancellationReason(Request $request): ?string
    {
        $validated = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $reason = trim((string) ($validated['cancellation_reason'] ?? ''));

        return $reason !== '' ? $reason : null;
    }

    private function ownedProfessional(Request $request): HealthProfessional
    {
        $professional = $request->user()?->healthProfessional;

        abort_if($professional === null, 404);

        return $professional;
    }

    private function ensureOwns(Request $request, HealthProfessional $professional): void
    {
        abort_unless(
            (int) $request->user()?->id === (int) $professional->user_id,
            403,
            'You can only manage your own healthcare profile.'
        );
    }

    private function ensureOwnsBooking(
        Request $request,
        HealthProfessional $professional,
        HealthBooking $booking,
    ): void {
        $this->ensureOwns($request, $professional);

        abort_unless(
            (int) $booking->health_professional_id === (int) $professional->id,
            404
        );
    }
}
