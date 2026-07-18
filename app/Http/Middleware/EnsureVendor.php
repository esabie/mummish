<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use App\Support\AppLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->role !== UserRole::Vendor) {
            AppLog::warning('[Vendor] Access denied — not a vendor.', [
                'user_id' => $user?->id,
                'role' => $user?->role?->value,
            ]);

            abort(403, 'Vendor access required.');
        }

        if ($user->vendorApplication === null) {
            AppLog::info('[Vendor] Redirecting to signup — no application on file.', [
                'user_id' => $user->id,
            ]);

            return redirect()->route('vendor.signup')
                ->with('info', 'Complete your seller application to access the vendor portal.');
        }

        return $next($request);
    }
}
