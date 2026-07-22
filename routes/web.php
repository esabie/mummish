<?php

use App\Http\Controllers\AdminSetupController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterCustomerController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ShortLinkController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\VendorStoreController;
use App\Http\Controllers\Vendor\VendorDashboardController;
use App\Http\Controllers\Vendor\VendorInventoryController;
use App\Http\Controllers\Vendor\VendorNotificationController;
use App\Http\Controllers\Vendor\VendorOrderController;
use App\Http\Controllers\Vendor\VendorPlaceholderController;
use App\Http\Controllers\Vendor\VendorProductController;
use App\Http\Controllers\Vendor\VendorProductImageController;
use App\Http\Controllers\VendorOnboardingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/r/{code}', ShortLinkController::class)
    ->where('code', '[A-Za-z0-9]{6,16}')
    ->middleware('throttle:60,1')
    ->name('short-link');

Route::middleware('throttle:10,1')->group(function () {
    Route::get('/admin-setup/{token}', [AdminSetupController::class, 'create'])
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('admin.setup.create');
    Route::post('/admin-setup/{token}', [AdminSetupController::class, 'store'])
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('admin.setup.store');
});

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('/shipping', function () {
    return Inertia::render('Shipping');
})->name('shipping');

Route::get('/terms', function () {
    return Inertia::render('Terms');
})->name('terms');

Route::get('/billing', function () {
    return Inertia::render('Billing');
})->name('billing');

Route::get('/privacy', function () {
    return Inertia::render('Privacy');
})->name('privacy');

Route::get('/faq', function () {
    return Inertia::render('Faq');
})->name('faq');

Route::get('/contact', function () {
    return Inertia::render('Contact');
})->name('contact');

Route::post('/newsletter', [NewsletterCustomerController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('newsletter.store');

Route::get('/sell', [VendorOnboardingController::class, 'create'])->name('vendor.signup');
Route::post('/sell', [VendorOnboardingController::class, 'store'])->name('vendor.signup.store');

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::post('/shop/cart-stock', [ShopController::class, 'cartStock'])->name('shop.cart-stock');
Route::get('/shop/products/{id}', [ShopController::class, 'show'])
    ->whereNumber('id')
    ->name('shop.show');

Route::get('/shops', [VendorStoreController::class, 'index'])->name('shops.index');
Route::get('/shops/{slug}', [VendorStoreController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('shops.show');
Route::get('/shops/{slug}/products/{id}', [VendorStoreController::class, 'product'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->whereNumber('id')
    ->name('shops.products.show');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout/promo', [CheckoutController::class, 'validatePromo'])->name('checkout.promo.validate');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/callback', [CheckoutController::class, 'callback'])->name('checkout.callback');
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/failed/{order}', [CheckoutController::class, 'failed'])->name('checkout.failed');

Route::get('/orders/track', [OrderTrackingController::class, 'create'])->name('orders.track');
Route::post('/orders/track', [OrderTrackingController::class, 'lookup'])
    ->middleware('throttle:10,1')
    ->name('orders.track.lookup');
Route::get('/orders/{order}/track', [OrderTrackingController::class, 'show'])->name('orders.track.show');
Route::post('/orders/{order}/track/received', [OrderTrackingController::class, 'confirmReceipt'])
    ->middleware('throttle:10,1')
    ->name('orders.track.received');

Route::post('/webhooks/paystack', PaystackWebhookController::class)->name('webhooks.paystack');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/', [VendorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/inventory', [VendorInventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/create', [VendorProductController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [VendorProductController::class, 'store'])->name('inventory.store');
    Route::post('/inventory/check-image', [VendorProductImageController::class, 'check'])->name('inventory.check-image');
    Route::get('/inventory/{product}/edit', [VendorProductController::class, 'edit'])->name('inventory.edit');
    Route::put('/inventory/{product}', [VendorProductController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{product}', [VendorProductController::class, 'destroy'])->name('inventory.destroy');
    Route::get('/orders', [VendorOrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/{order}/fulfill', [VendorOrderController::class, 'fulfill'])->name('orders.fulfill');
    Route::get('/notifications', [VendorNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [VendorNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [VendorNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/customers', [VendorPlaceholderController::class, 'customers'])->name('customers.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

require __DIR__.'/auth.php';
