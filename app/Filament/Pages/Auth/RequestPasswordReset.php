<?php

namespace App\Filament\Pages\Auth;

use App\Jobs\SendPasswordResetSms;
use App\Models\User;
use App\Services\ShortLinkService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Password;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();
        $email = (string) ($data['email'] ?? '');

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if (
            $user === null
            || ($user instanceof FilamentUser && ! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            $this->getFailureNotification(Password::INVALID_USER)?->send();

            return;
        }

        $phone = $user->passwordResetPhone();
        if ($phone === null) {
            Notification::make()
                ->title('We could not send a password reset SMS for this admin account.')
                ->body('This admin has no phone number on file. Ask another admin to add one under Admin users.')
                ->danger()
                ->send();

            return;
        }

        $token = Password::broker(Filament::getAuthPasswordBroker())->createToken($user);
        $resetUrl = Filament::getResetPasswordUrl($token, $user);
        $ttlMinutes = (int) config('auth.passwords.users.expire', 60);
        $shortUrl = app(ShortLinkService::class)->create($resetUrl, $ttlMinutes);
        $firstName = trim(strtok((string) $user->name, ' ') ?: 'there');

        SendPasswordResetSms::dispatch($phone, $firstName, $shortUrl);

        if (class_exists(PasswordResetLinkSent::class)) {
            event(new PasswordResetLinkSent($user));
        }

        Notification::make()
            ->title('We have sent your password reset link via SMS.')
            ->success()
            ->send();

        $this->form->fill();
    }

    public function getHeading(): string | Htmlable
    {
        return 'Forgot your password?';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Enter your admin email and we will text you a reset link.';
    }
}
