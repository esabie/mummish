<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendPasswordResetSms;
use App\Models\User;
use App\Services\ShortLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * Email delivery is not configured yet, so vendors with a phone on file
     * receive the reset link by SMS instead.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::query()
            ->with('vendorApplication')
            ->where('email', $request->string('email')->toString())
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => [trans(Password::INVALID_USER)],
            ]);
        }

        $phone = $user->passwordResetPhone();

        if ($phone === null) {
            throw ValidationException::withMessages([
                'email' => ['We could not send a password reset SMS for this account. Please ensure a phone number is on file, or contact support.'],
            ]);
        }

        $token = Password::broker()->createToken($user);
        $resetUrl = url('/reset-password/'.$token.'?email='.urlencode($user->getEmailForPasswordReset()));
        $ttlMinutes = (int) config('auth.passwords.users.expire', 60);
        $shortUrl = app(ShortLinkService::class)->create($resetUrl, $ttlMinutes);

        $firstName = trim((string) ($user->vendorApplication?->first_name ?? ''));
        if ($firstName === '') {
            $firstName = trim(strtok((string) $user->name, ' ') ?: 'there');
        }

        SendPasswordResetSms::dispatch($phone, $firstName, $shortUrl);

        return back()->with('status', 'We have sent your password reset link via SMS.');
    }
}
