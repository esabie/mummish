<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'appUrl' => rtrim((string) config('app.url'), '/'),
            'appName' => (string) config('app.name', 'Mummish'),
            'seo' => [
                'title' => (string) (filled(config('seo.title'))
                    ? config('seo.title')
                    : 'Mummish | Marketplace for mothers & kids in Ghana'),
                'description' => (string) (filled(config('seo.description'))
                    ? config('seo.description')
                    : 'Marketplace for the modern mother. Shop baby clothes, kids products, and family essentials from trusted local sellers across Ghana.'),
                'taglines' => array_values(array_filter(config('seo.taglines', []))),
            ],
            'auth' => [
                'user' => $user,
            ],
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'vendorNotifications' => [
                'unread_count' => fn () => $user && $user->isVendor()
                    ? $user->unreadNotifications()->count()
                    : 0,
            ],
            'flash' => [
                'vendorApplicationSubmitted' => fn () => $request->session()->get('vendorApplicationSubmitted'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
