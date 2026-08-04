<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreHealthProfessionalRequest;
use App\Models\HealthProfessional;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        DB::transaction(function () use ($request, $data, &$user, &$professional) {
            $imagePath = $request->file('image')?->store('health-professionals', 'public');

            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower(trim($data['email'])),
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
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
            ]);
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect()
            ->route('health-professionals.edit', $professional)
            ->with('success', 'Account created. Add your visit modes, services, and weekly availability to go live.');
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
