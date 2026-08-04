<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHealthProfessionalRequest;
use App\Models\HealthProfessional;
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

        return Inertia::render('HealthServices/Dashboard', [
            'professionals' => [[
                'id' => $professional->id,
                'name' => $professional->name,
                'slug' => $professional->slug,
                'title' => $professional->title,
                'specialty' => $professional->specialty,
                'visit_modes' => $professional->visit_modes ?? [],
                'is_active' => $professional->is_active,
                'services_count' => $professional->services_count,
                'availability_count' => $professional->availability_count,
                'updated_at' => optional($professional->updated_at)->toDateTimeString(),
            ]],
        ]);
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
                'is_active' => (bool) ($request->boolean('is_active')),
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
}
