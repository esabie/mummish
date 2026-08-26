<?php

namespace App\Http\Controllers;

use App\Enums\HealthProfessionalApprovalStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreHealthProfessionalRequest;
use App\Models\HealthProfessional;
use App\Models\User;
use App\Support\AppLog;
use App\Support\LogSanitizer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HealthProfessionalOnboardingController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user?->isHealthProfessional() && $user->healthProfessional) {
            return redirect()->route('health-professionals.dashboard');
        }

        AppLog::info('[HealthProfessionalOnboarding] Signup page requested.', [
            'authenticated' => $user !== null,
        ]);

        return Inertia::render('HealthServices/SignUp');
    }

    public function store(StoreHealthProfessionalRequest $request): RedirectResponse
    {
        if ($request->user()?->isHealthProfessional() && $request->user()->healthProfessional) {
            return redirect()->route('health-professionals.dashboard');
        }

        $data = $request->validated();
        $user = null;
        $professional = null;

        AppLog::info('[HealthProfessionalOnboarding] Signup submitted.', [
            'email_masked' => LogSanitizer::maskEmail((string) ($data['email'] ?? '')),
            'has_image' => $request->hasFile('image'),
        ]);

        DB::transaction(function () use ($request, $data, &$user, &$professional) {
            $imagePath = $request->file('image')?->store('health-professionals', 'public');

            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower(trim($data['email'])),
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => UserRole::HealthProfessional,
            ]);

            $professional = HealthProfessional::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'title' => $data['title'],
                'specialty' => $data['specialty'],
                'about' => $data['about'],
                'location' => $data['location'],
                'phone' => $data['phone'],
                'email' => strtolower(trim($data['email'])),
                'visit_modes' => ['Virtual'],
                'languages' => [],
                'highlights' => [],
                'booking_note' => 'Appointments are confirmed after review.',
                'experience' => HealthProfessional::formatExperience(
                    (int) $data['experience_amount'],
                    (string) $data['experience_unit'],
                ),
                'image_path' => $imagePath,
                'is_active' => false,
                'approval_status' => HealthProfessionalApprovalStatus::Pending,
            ]);
        });

        event(new Registered($user));
        Auth::login($user);

        AppLog::info('[HealthProfessionalOnboarding] Signup completed.', [
            'user_id' => $user->id,
            'professional_id' => $professional->id,
        ]);

        return redirect()
            ->route('health-professionals.edit', $professional)
            ->with('success', 'Account created. Finish your profile, then wait for admin approval before you appear on Health Services.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (HealthProfessional::query()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
