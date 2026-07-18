<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorPlaceholderController extends Controller
{
    public function orders(Request $request): Response
    {
        return Inertia::render('Vendor/Orders/Index', [
            'shopName' => $request->user()->vendorApplication->shop_name,
        ]);
    }

    public function customers(Request $request): Response
    {
        return Inertia::render('Vendor/Customers/Index', [
            'shopName' => $request->user()->vendorApplication->shop_name,
        ]);
    }
}
