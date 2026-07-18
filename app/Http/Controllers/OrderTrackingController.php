<?php

namespace App\Http\Controllers;

use App\Http\Requests\LookupOrderRequest;
use App\Models\Order;
use App\Services\OrderTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderTrackingController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Orders/Track', [
            'prefill' => [
                'order_number' => (string) $request->query('order_number', ''),
                'customer_email' => (string) $request->query('email', ''),
            ],
            'error' => session('error'),
        ]);
    }

    public function lookup(LookupOrderRequest $request, OrderTrackingService $tracking): RedirectResponse
    {
        $validated = $request->validated();

        $order = $tracking->findForLookup(
            $validated['order_number'],
            $validated['customer_email'],
        );

        if ($order === null) {
            return back()
                ->withInput()
                ->with('error', 'We could not find an order with those details. Check your order number and email, then try again.');
        }

        $tracking->markVerified($order);

        return redirect()->route('orders.track.show', $order);
    }

    public function show(Request $request, Order $order, OrderTrackingService $tracking): Response|RedirectResponse
    {
        if (! $tracking->canView($request, $order)) {
            return redirect()
                ->route('orders.track')
                ->with('error', 'Enter your order number and email to view tracking for this order.');
        }

        return Inertia::render('Orders/Show', [
            'order' => $tracking->formatTrackingPayload($order),
            'status' => session('status'),
            'error' => session('error'),
        ]);
    }

    public function confirmReceipt(Request $request, Order $order, OrderTrackingService $tracking): RedirectResponse
    {
        if (! $tracking->canView($request, $order)) {
            return redirect()
                ->route('orders.track')
                ->with('error', 'Enter your order number and email to confirm delivery.');
        }

        try {
            $tracking->confirmReceipt($order);
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('orders.track.show', $order)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('orders.track.show', $order)
            ->with('status', 'Thanks — your order is marked as received.');
    }
}
