<?php

namespace App\Http\Controllers;

use App\Models\HealthProfessional;
use Inertia\Inertia;
use Inertia\Response;

class HealthServiceController extends Controller
{
    public function index(): Response
    {
        $dbProfessionals = HealthProfessional::query()
            ->with(['services' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
            ->where('is_active', true)
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

    public function show(string $slug): Response
    {
        $dbProfessional = HealthProfessional::query()
            ->with([
                'services' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
                'availability' => fn ($q) => $q->where('is_active', true)->orderBy('day_of_week')->orderBy('start_time'),
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($dbProfessional !== null) {
            $professional = [
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
                'rate_card' => $dbProfessional->services->map(fn ($service) => [
                    'service' => $service->name,
                    'price' => 'GHS '.$service->price_cedis,
                    'mode' => $service->visit_mode,
                ])->values()->all(),
                'slots' => $dbProfessional->availability->map(fn ($window) => [
                    'date' => $this->weekdayLabel((int) $window->day_of_week),
                    'day' => $this->weekdayLabel((int) $window->day_of_week),
                    'times' => $this->timesFromWindow((string) $window->start_time, (string) $window->end_time),
                ])->values()->all(),
                'booking_note' => $dbProfessional->booking_note ?? 'Appointments are confirmed after review.',
                'rating' => (float) $dbProfessional->rating,
                'review_count' => (int) $dbProfessional->review_count,
                'image' => $dbProfessional->image_path
                    ? asset('storage/'.$dbProfessional->image_path)
                    : (string) ($dbProfessional->image_url ?? ''),
            ];
        } else {
            $professional = collect(config('marketplace.health_services_professionals', []))
                ->first(fn (array $item) => ($item['slug'] ?? null) === $slug);
        }

        abort_if($professional === null, 404);

        return Inertia::render('HealthServices/Show', [
            'professional' => $professional,
        ]);
    }

    private function weekdayLabel(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            default => 'Day',
        };
    }

    /**
     * @return array<int, string>
     */
    private function timesFromWindow(string $startTime, string $endTime): array
    {
        $times = [];
        $cursor = strtotime($startTime);
        $end = strtotime($endTime);

        while ($cursor < $end) {
            $times[] = date('g:i A', $cursor);
            $cursor = strtotime('+30 minutes', $cursor);
        }

        return $times;
    }
}
