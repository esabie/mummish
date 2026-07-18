<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\AppLog;
use App\Support\LogSanitizer;
use App\Support\UserHome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        AppLog::info('[Auth] Login attempt.', [
            'email_masked' => LogSanitizer::maskEmail($request->string('email')->toString()),
            'remember' => $request->boolean('remember'),
        ]);

        try {
            $request->authenticate();
        } catch (\Illuminate\Validation\ValidationException $exception) {
            AppLog::warning('[Auth] Login failed.', [
                'email_masked' => LogSanitizer::maskEmail($request->string('email')->toString()),
            ]);

            throw $exception;
        }

        $request->session()->regenerate();

        AppLog::info('[Auth] Login succeeded.', [
            'user_id' => $request->user()?->id,
            'role' => $request->user()?->role?->value,
        ]);

        return redirect()->intended(UserHome::path($request->user()));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        AppLog::info('[Auth] Logout requested.', [
            'user_id' => $request->user()?->id,
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
