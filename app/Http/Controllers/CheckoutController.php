<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutRequest;
use App\Http\Requests\ValidatePromoCodeRequest;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\PaystackService;
use App\Services\PromoCodeService;
use App\Services\ShippingCalculator;
use App\Support\LogSanitizer;
use App\Support\PublicStorageUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class CheckoutController extends Controller
{
    public function index(Request $request, PaystackService $paystack, ShippingCalculator $shipping): Response|RedirectResponse
    {
        Log::info('Checkout: index requested.', [
            'user_id' => $request->user()?->id,
            'is_inertia' => $request->header('X-Inertia') === 'true',
            'paystack_configured' => $paystack->publicKey() !== '',
        ]);

        if (! $paystack->publicKey()) {
            Log::warning('Checkout: index blocked — Paystack public key missing.');

            return redirect()
                ->route('shop.index')
                ->with('error', 'Online checkout is not available right now. Please try again later.');
        }

        $user = $request->user();

        Log::debug('Checkout: rendering checkout page.', [
            'user_id' => $user?->id,
            'customer_prefill' => $user ? [
                'email_masked' => LogSanitizer::maskEmail($user->email),
            ] : null,
        ]);

        return Inertia::render('Checkout/Index', [
            'paystackPublicKey' => $paystack->publicKey(),
            'shippingRatesByRegion' => $shipping->regionRates(),
            'shippingRatesByCity' => $shipping->cityRates(),
            'ghanaRegions' => config('marketplace.ghana_regions', []),
            'ghanaCitiesByRegion' => config('ghana_cities.by_region', []),
            'customer' => $user ? [
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
        ]);
    }

    public function store(StoreCheckoutRequest $request, CheckoutService $checkout): RedirectResponse|SymfonyResponse
    {
        $validated = $request->validated();

        Log::info('Checkout: store requested.', [
            'user_id' => $request->user()?->id,
            'is_inertia' => $request->header('X-Inertia') === 'true',
            'item_count' => count($validated['items'] ?? []),
            'items' => collect($validated['items'] ?? [])->map(fn (array $item) => [
                'product_id' => $item['product_id'] ?? null,
                'quantity' => $item['quantity'] ?? null,
                'has_attributes' => ! empty($item['attributes']),
            ])->all(),
            'shipping' => LogSanitizer::maskShipping($validated),
        ]);

        try {
            $user = $request->user();
            $shipping = $request->safe()->only([
                'customer_name',
                'customer_email',
                'customer_phone',
                'shipping_address_line1',
                'shipping_address_line2',
                'shipping_city',
                'shipping_region',
                'shipping_notes',
            ]);

            if ($user === null) {
                [$user, $createdAccount] = $checkout->resolveGuestCustomer($shipping);

                if ($createdAccount) {
                    Auth::login($user);
                    $request->session()->regenerate();
                }
            }

            $order = $checkout->createPendingOrder(
                items: $validated['items'],
                shipping: $shipping,
                user: $user,
                promoCode: $validated['promo_code'] ?? null,
            );

            Log::info('Checkout: pending order created.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'paystack_reference' => $order->paystack_reference,
                'total_cents' => $order->total_cents,
                'line_count' => $order->items->count(),
            ]);

            $payment = $checkout->startPaystackPayment($order);

            Log::info('Checkout: redirecting to Paystack.', [
                'order_id' => $order->id,
                'paystack_reference' => $order->paystack_reference,
                'authorization_url_host' => parse_url($payment['authorization_url'], PHP_URL_HOST),
                'is_inertia' => $request->header('X-Inertia') === 'true',
                'note' => 'Phone number is stored on the order but is not sent to Paystack initialize API.',
            ]);

            // Inertia XHR posts cannot follow external redirects — force a full navigation to Paystack.
            return Inertia::location($payment['authorization_url']);
        } catch (RuntimeException $exception) {
            Log::error('Checkout: payment start failed.', [
                'message' => $exception->getMessage(),
                'user_id' => $request->user()?->id,
                'item_count' => count($validated['items'] ?? []),
                'shipping' => LogSanitizer::maskShipping($validated),
            ]);

            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }
    }

    public function validatePromo(ValidatePromoCodeRequest $request, CheckoutService $checkout, PromoCodeService $promoCodes): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();
        $lines = $checkout->previewCartLines($validated['items']);
        $subtotalCents = (int) $lines->sum('line_total_cents');
        $result = $promoCodes->apply($validated['promo_code'], $subtotalCents);

        return response()->json([
            'promo_code' => $result['promo']?->code,
            'discount_cents' => $result['discount_cents'],
            'discount_label' => $result['description'],
            'subtotal_cents' => $subtotalCents,
        ]);
    }

    public function callback(Request $request, CheckoutService $checkout): RedirectResponse
    {
        $reference = (string) $request->query('reference', '');

        Log::info('Checkout: callback received.', [
            'reference' => $reference !== '' ? $reference : null,
            'query_keys' => array_keys($request->query()),
            'user_id' => $request->user()?->id,
        ]);

        if ($reference === '') {
            Log::warning('Checkout: callback missing reference.');

            return redirect()
                ->route('shop.index')
                ->with('error', 'Missing payment reference.');
        }

        $order = $checkout->findOrderByReference($reference);

        if ($order === null) {
            Log::warning('Checkout: callback order not found.', ['reference' => $reference]);

            return redirect()
                ->route('shop.index')
                ->with('error', 'We could not find your order.');
        }

        Log::debug('Checkout: callback order resolved.', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status->value,
            'status' => $order->status->value,
            'total_cents' => $order->total_cents,
        ]);

        if ($order->isPaid()) {
            Log::info('Checkout: callback skipped — order already paid.', [
                'order_id' => $order->id,
                'reference' => $reference,
            ]);

            return redirect()->route('checkout.success', $order);
        }

        try {
            $paystack = app(PaystackService::class);
            $data = $paystack->verifyTransaction($reference);

            Log::debug('Checkout: Paystack verify response received.', [
                'reference' => $reference,
                'order_id' => $order->id,
                'paystack_status' => $data['status'] ?? null,
                'paystack_amount' => $data['amount'] ?? null,
                'expected_amount' => $order->total_cents,
                'paystack_channel' => $data['channel'] ?? null,
            ]);

            $checkout->markOrderPaidFromPaystack($order, $data);

            Log::info('Checkout: payment confirmed via callback.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'reference' => $reference,
            ]);

            return redirect()->route('checkout.success', $order);
        } catch (Throwable $exception) {
            Log::warning('Checkout: callback verification failed.', [
                'reference' => $reference,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'message' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            return redirect()
                ->route('checkout.failed', $order)
                ->with('error', 'Payment could not be confirmed. If you were charged, contact support with your order number.');
        }
    }

    public function success(Order $order): Response
    {
        Log::info('Checkout: success page requested.', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'is_paid' => $order->isPaid(),
        ]);

        if (! $order->isPaid()) {
            Log::warning('Checkout: success page denied — order not paid.', [
                'order_id' => $order->id,
                'payment_status' => $order->payment_status->value,
            ]);

            abort(404);
        }

        $order->load('items');

        return Inertia::render('Checkout/Success', [
            'order' => $this->formatOrder($order),
        ]);
    }

    public function failed(Order $order): Response|RedirectResponse
    {
        Log::info('Checkout: failed page requested.', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status->value,
            'is_paid' => $order->isPaid(),
        ]);

        if ($order->isPaid()) {
            Log::info('Checkout: failed page redirecting to success — order is paid.', [
                'order_id' => $order->id,
            ]);

            return redirect()->route('checkout.success', $order);
        }

        return Inertia::render('Checkout/Failed', [
            'order' => [
                'order_number' => $order->order_number,
                'formatted_total' => $order->formattedTotal(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatOrder(Order $order): array
    {
        return [
            'order_number' => $order->order_number,
            'formatted_total' => $order->formattedTotal(),
            'promo_code' => $order->promo_code,
            'discount_cents' => $order->discount_cents,
            'formatted_discount' => $order->discount_cents > 0
                ? 'GHS '.number_format($order->discount_cents / 100, 2)
                : null,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'shipping_address_line1' => $order->shipping_address_line1,
            'shipping_address_line2' => $order->shipping_address_line2,
            'shipping_city' => $order->shipping_city,
            'shipping_region' => $order->shipping_region,
            'items' => $order->items->map(fn ($item) => [
                'title' => $item->product_title,
                'brand' => $item->product_brand,
                'quantity' => $item->quantity,
                'formatted_line_total' => $item->formattedLineTotal(),
                // Stored URLs may point at an old host; rebuild for the current one.
                'image' => PublicStorageUrl::fromStored($item->product_image),
                'attributes' => $item->attributes,
            ])->values()->all(),
        ];
    }
}
