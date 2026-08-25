<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHealthBookingRequest;
use App\Models\HealthBooking;
use App\Models\HealthProfessional;
use App\Models\HealthProfessionalService;
use App\Services\HealthBookingPaymentService;
use App\Services\HealthBookingReviewService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class HealthServiceController extends Controller
{
    public function index(): Response
    {
        $dbProfessionals = HealthProfessional::query()
            ->with(['services' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
            ->publiclyVisible()
            ->latest('id')
            ->get();

        $professionals = $dbProfessionals->isNotEmpty()
            ? $dbProfessionals->map(function (HealthProfessional $professional) {
                $firstService = $professional->services->first();

                return [
                    'slug' => $professional->slug,
                    'name' => $professional->name,
                    'title' => $professional->title,
                    'specialty' => $professional->specialty,
                    'service' => $firstService?->name ?? 'Consultation',
                    'rate' => $firstService ? 'GHS '.$firstService->price_cedis : 'GHS 0 / session',
                    'rate_value' => $firstService?->price_cedis ?? 0,
                    'visit_modes' => $professional->visit_modes ?? [],
                    'experience' => $professional->experience ?? '',
                    'location' => $professional->location ?? '',
                    'availability' => 'Set from weekly schedule',
                    'rating' => (float) $professional->rating,
                    'review_count' => (int) $professional->review_count,
                    'next_available' => 'Check profile for next slot',
                    'response_time' => $professional->response_time ?? '',
                    'slots' => [],
                    'image' => $professional->image_path
                        ? asset('storage/'.$professional->image_path)
                        : (string) ($professional->image_url ?? ''),
                ];
            })->values()->all()
            : collect(config('marketplace.health_services_professionals', []))
                ->map(fn (array $professional) => [
                    'slug' => (string) ($professional['slug'] ?? ''),
                    'name' => (string) ($professional['name'] ?? 'Healthcare Professional'),
                    'title' => (string) ($professional['title'] ?? 'Specialist'),
                    'specialty' => (string) ($professional['specialty'] ?? 'General Care'),
                    'service' => (string) ($professional['service'] ?? 'Consultation'),
                    'rate' => (string) ($professional['rate'] ?? 'GHS 0 / session'),
                    'rate_value' => (int) ($professional['rate_value'] ?? 0),
                    'visit_modes' => collect($professional['visit_modes'] ?? [])->values()->all(),
                    'experience' => (string) ($professional['experience'] ?? ''),
                    'location' => (string) ($professional['location'] ?? ''),
                    'availability' => (string) ($professional['availability'] ?? ''),
                    'rating' => (float) ($professional['rating'] ?? 0),
                    'review_count' => (int) ($professional['review_count'] ?? 0),
                    'next_available' => (string) ($professional['next_available'] ?? ''),
                    'response_time' => (string) ($professional['response_time'] ?? ''),
                    'slots' => collect($professional['slots'] ?? [])
                        ->map(fn (array $day) => [
                            'date' => (string) ($day['date'] ?? ''),
                            'day' => (string) ($day['day'] ?? ''),
                            'date_iso' => null,
                            'times' => collect($day['times'] ?? [])->values()->all(),
                        ])->values()->all(),
                    'image' => (string) ($professional['image'] ?? ''),
                ])
                ->values()
                ->all();

        return Inertia::render('HealthServices/Index', [
            'professionals' => $professionals,
        ]);
    }

    public function show(string $slug, HealthBookingReviewService $reviews): Response
    {
        $dbProfessional = HealthProfessional::query()
            ->with([
                'services' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
                'availability' => fn ($q) => $q->where('is_active', true)->orderBy('day_of_week')->orderBy('start_time'),
                'reviews' => fn ($q) => $q->published()->latest()->limit(20),
            ])
            ->where('slug', $slug)
            ->publiclyVisible()
            ->first();

        if ($dbProfessional !== null) {
            $professional = [
                'id' => $dbProfessional->id,
                'slug' => $dbProfessional->slug,
                'name' => $dbProfessional->name,
                'title' => $dbProfessional->title,
                'specialty' => $dbProfessional->specialty,
                'service' => $dbProfessional->services->first()?->name ?? 'Consultation',
                'rate' => $dbProfessional->services->first()
                    ? 'GHS '.$dbProfessional->services->first()->price_cedis
                    : 'GHS 0 / session',
                'rate_value' => $dbProfessional->services->first()?->price_cedis ?? 0,
                'visit_modes' => $dbProfessional->visit_modes ?? [],
                'experience' => $dbProfessional->experience ?? '',
                'location' => $dbProfessional->location ?? '',
                'availability' => 'Based on weekly schedule',
                'next_available' => 'Choose a date to view times',
                'response_time' => $dbProfessional->response_time ?? '',
                'languages' => $dbProfessional->languages ?? [],
                'about' => $dbProfessional->about ?? '',
                'highlights' => $dbProfessional->highlights ?? [],
                'bookable' => $dbProfessional->services->isNotEmpty() && $dbProfessional->availability->isNotEmpty(),
                'rate_card' => $dbProfessional->services->map(fn ($service) => [
                    'id' => $service->id,
                    'service' => $service->name,
                    'price' => 'GHS '.$service->price_cedis,
                    'mode' => $service->visit_mode,
                    'duration_minutes' => $service->duration_minutes,
                ])->values()->all(),
                'slots' => $this->upcomingSlots($dbProfessional),
                'booking_note' => $dbProfessional->booking_note ?? 'Appointments are confirmed after review.',
                'rating' => (float) $dbProfessional->rating,
                'review_count' => (int) $dbProfessional->review_count,
                'reviews' => $dbProfessional->reviews
                    ->map(fn ($review) => $reviews->formatReviewCard($review))
                    ->values()
                    ->all(),
                'image' => $dbProfessional->image_path
                    ? asset('storage/'.$dbProfessional->image_path)
                    : (string) ($dbProfessional->image_url ?? ''),
            ];
        } else {
            $configProfessional = collect(config('marketplace.health_services_professionals', []))
                ->first(fn (array $item) => ($item['slug'] ?? null) === $slug);

            abort_if($configProfessional === null, 404);

            $professional = array_merge($configProfessional, [
                'bookable' => false,
                'rate_card' => collect($configProfessional['rate_card'] ?? [])
                    ->map(fn (array $item) => [
                        'id' => null,
                        'service' => (string) ($item['service'] ?? 'Consultation'),
                        'price' => (string) ($item['price'] ?? 'GHS 0'),
                        'mode' => (string) ($item['mode'] ?? 'Virtual'),
                    ])->values()->all(),
                'slots' => collect($configProfessional['slots'] ?? [])
                    ->map(fn (array $day) => [
                        'date' => (string) ($day['date'] ?? ''),
                        'day' => (string) ($day['day'] ?? ''),
                        'date_iso' => null,
                        'times' => collect($day['times'] ?? [])
                            ->map(fn ($time) => is_array($time)
                                ? [
                                    'label' => (string) ($time['label'] ?? ''),
                                    'booked' => (bool) ($time['booked'] ?? false),
                                ]
                                : [
                                    'label' => (string) $time,
                                    'booked' => false,
                                ])
                            ->values()
                            ->all(),
                    ])->values()->all(),
            ]);
        }

        return Inertia::render('HealthServices/Show', [
            'professional' => $professional,
        ]);
    }

    public function storeBooking(
        StoreHealthBookingRequest $request,
        string $slug,
        HealthBookingPaymentService $payments,
    ): RedirectResponse|SymfonyResponse {
        $professional = HealthProfessional::query()
            ->where('slug', $slug)
            ->publiclyVisible()
            ->firstOrFail();

        $data = $request->validated();

        $service = HealthProfessionalService::query()
            ->whereKey($data['health_professional_service_id'])
            ->where('health_professional_id', $professional->id)
            ->where('is_active', true)
            ->first();

        if ($service === null) {
            throw ValidationException::withMessages([
                'health_professional_service_id' => 'That service is not available for this provider.',
            ]);
        }

        if ($service->visit_mode !== $data['visit_mode']) {
            throw ValidationException::withMessages([
                'visit_mode' => 'The selected visit type does not match this service.',
            ]);
        }

        if ((int) $service->price_cedis < 1) {
            throw ValidationException::withMessages([
                'health_professional_service_id' => 'This service does not have a valid price yet.',
            ]);
        }

        $appointmentDate = Carbon::parse($data['appointment_date'])->startOfDay();
        $appointmentTime = $data['appointment_time'];

        if (! $this->slotIsOffered($professional, $appointmentDate, $appointmentTime)) {
            throw ValidationException::withMessages([
                'appointment_time' => 'That time slot is not available. Please choose another.',
            ]);
        }

        $slotTaken = HealthBooking::query()
            ->where('health_professional_id', $professional->id)
            ->whereDate('appointment_date', $appointmentDate->toDateString())
            ->holdingSlot()
            ->get()
            ->contains(function (HealthBooking $existing) use ($appointmentTime) {
                return Carbon::parse((string) $existing->appointment_time)->format('H:i') === $appointmentTime;
            });

        if ($slotTaken) {
            throw ValidationException::withMessages([
                'appointment_time' => 'That time slot was just taken. Please choose another.',
            ]);
        }

        $amountCents = (int) $service->price_cedis * 100;
        $split = $payments->splitForAmount($amountCents);
        $reference = HealthBooking::generateReference();

        $booking = DB::transaction(function () use (
            $professional,
            $service,
            $data,
            $appointmentDate,
            $appointmentTime,
            $amountCents,
            $split,
            $reference,
        ) {
            return HealthBooking::create([
                'reference' => $reference,
                'health_professional_id' => $professional->id,
                'health_professional_service_id' => $service->id,
                'patient_name' => $data['patient_name'],
                'patient_email' => $data['patient_email'],
                'patient_phone' => $data['patient_phone'],
                'appointment_date' => $appointmentDate->toDateString(),
                'appointment_time' => $appointmentTime,
                'visit_mode' => $data['visit_mode'],
                'status' => 'awaiting_payment',
                'notes' => $data['notes'] ?? null,
                'amount_cents' => $amountCents,
                'commission_cents' => $split['commission_cents'],
                'professional_payout_cents' => $split['payout_cents'],
                'payment_status' => 'pending',
                'paystack_reference' => $reference,
                'payment_expires_at' => now()->addMinutes(HealthBooking::paymentHoldMinutes()),
            ]);
        });

        try {
            $paystack = $payments->startPaystackPayment($booking);
        } catch (Throwable $exception) {
            $booking->update([
                'status' => 'cancelled',
                'payment_status' => 'failed',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Unable to start payment.',
            ]);

            throw ValidationException::withMessages([
                'patient_email' => $exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : 'Unable to start payment. Please try again.',
            ]);
        }

        if (($paystack['authorization_url'] ?? '') === '') {
            throw ValidationException::withMessages([
                'patient_email' => 'Unable to start payment. Please try again.',
            ]);
        }

        return Inertia::location($paystack['authorization_url']);
    }

    /**
     * Build bookable slots for the next two weeks from weekly availability.
     *
     * @return array<int, array{date: string, day: string, date_iso: string, times: array<int, array{label: string, booked: bool}>}>
     */
    private function upcomingSlots(HealthProfessional $professional): array
    {
        $windowsByDay = $professional->availability->groupBy('day_of_week');
        $slots = [];
        $today = now()->startOfDay();
        $rangeEnd = $today->copy()->addDays(13);

        $bookedByDate = HealthBooking::query()
            ->where('health_professional_id', $professional->id)
            ->holdingSlot()
            ->whereDate('appointment_date', '>=', $today->toDateString())
            ->whereDate('appointment_date', '<=', $rangeEnd->toDateString())
            ->get()
            ->groupBy(fn (HealthBooking $booking) => Carbon::parse($booking->appointment_date)->toDateString())
            ->map(fn ($group) => $group
                ->map(fn (HealthBooking $booking) => Carbon::parse((string) $booking->appointment_time)->format('H:i'))
                ->unique()
                ->flip()
                ->all());

        for ($offset = 0; $offset < 14; $offset++) {
            $date = $today->copy()->addDays($offset);
            $dayWindows = $windowsByDay->get((int) $date->dayOfWeek, collect());

            if ($dayWindows->isEmpty()) {
                continue;
            }

            $times = [];
            foreach ($dayWindows as $window) {
                foreach ($this->timesFromWindow((string) $window->start_time, (string) $window->end_time) as $label) {
                    $times[$label] = true;
                }
            }

            $timeLabels = array_keys($times);

            if ($offset === 0) {
                $now = now();
                $timeLabels = array_values(array_filter(
                    $timeLabels,
                    fn (string $label) => Carbon::parse($date->toDateString().' '.$label)->gt($now)
                ));
            }

            if ($timeLabels === []) {
                continue;
            }

            $bookedTimes = $bookedByDate->get($date->toDateString(), []);

            $slots[] = [
                'date' => match ($offset) {
                    0 => 'Today',
                    1 => 'Tomorrow',
                    default => $date->format('D, j M'),
                },
                'day' => $date->format('D, j M'),
                'date_iso' => $date->toDateString(),
                'times' => array_values(array_map(
                    fn (string $label) => [
                        'label' => $label,
                        'booked' => isset($bookedTimes[Carbon::parse($label)->format('H:i')]),
                    ],
                    $timeLabels
                )),
            ];
        }

        return $slots;
    }

    private function slotIsOffered(HealthProfessional $professional, Carbon $date, string $time24): bool
    {
        $professional->loadMissing('availability');

        $windows = $professional->availability
            ->where('day_of_week', (int) $date->dayOfWeek)
            ->values();

        if ($windows->isEmpty()) {
            return false;
        }

        $offered = [];
        foreach ($windows as $window) {
            foreach ($this->timesFromWindow((string) $window->start_time, (string) $window->end_time) as $label) {
                $offered[] = Carbon::parse($label)->format('H:i');
            }
        }

        if (! in_array($time24, $offered, true)) {
            return false;
        }

        if ($date->isToday()) {
            return Carbon::parse($date->toDateString().' '.$time24)->gt(now());
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function timesFromWindow(string $startTime, string $endTime): array
    {
        $times = [];
        $cursor = strtotime($startTime);
        $end = strtotime($endTime);

        while ($cursor !== false && $end !== false && $cursor < $end) {
            $times[] = date('g:i A', $cursor);
            $cursor = strtotime('+30 minutes', $cursor);
        }

        return $times;
    }
}
