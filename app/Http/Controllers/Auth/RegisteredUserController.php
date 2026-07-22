<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EmailRoleConflict;
use App\Support\SafeRedirect;
use App\Support\UserHome;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): Response
    {
        SafeRedirect::remember($request->query('redirect'));

        return Inertia::render('Auth/Register', [
            'redirect' => SafeRedirect::sanitize($request->query('redirect')),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        SafeRedirect::remember($request->input('redirect') ?? $request->query('redirect'));

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:9', 'max:20', 'regex:/^[\d\s+()-]+$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'phone.regex' => 'Please enter a valid phone number.',
        ]);

        $conflict = EmailRoleConflict::customerRegistrationMessage($request->string('email')->toString());

        if ($conflict !== null) {
            throw ValidationException::withMessages([
                'email' => $conflict,
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => UserRole::Customer,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->intended(UserHome::path($user));
    }
}
