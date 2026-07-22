<?php

namespace App\Http\Controllers;

use App\Services\CustomerOrderHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CustomerOrderHistory $orders): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isVendor()) {
            return redirect()->route('vendor.inventory.index');
        }

        if ($user->isAdmin()) {
            return redirect('/admin');
        }

        $recentOrders = $orders->forUser($user, 5);

        return Inertia::render('Dashboard', [
            'recentOrders' => $recentOrders,
            'orderCount' => $user->orders()->count(),
        ]);
    }
}
