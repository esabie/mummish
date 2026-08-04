<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Support\AppLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHealthProfessional
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->role !== UserRole::HealthProfessional) {
            AppLog::warning('[HealthProfessional] Access denied — not a health professional.', [
                'user_id' => $user?->id,
                'role' => $user?->role?->value,
            ]);

            abort(403, 'Health professional access required.');
        }

        if ($user->healthProfessional === null) {
            AppLog::info('[HealthProfessional] Redirecting to signup — no profile on file.', [
                'user_id' => $user->id,
            ]);

            return redirect()->route('health-professionals.signup')
                ->with('info', 'Complete your healthcare professional registration to access your dashboard.');
        }

        return $next($request);
    }
}
