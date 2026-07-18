<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\SendOrderPaidSms;
use App\Jobs\SendVendorNewOrderSms;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\VendorNewOrderNotification;
use App\Support\LogSanitizer;
use App\Support\PublicStorageUrl;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CheckoutService
{
    public function __construct(
        private readonly PaystackService $paystack,
        private readonly ShippingCalculator $shippingCalculator,
        private readonly PromoCodeService $promoCodes,
        private readonly VendorReferralRewardService $referralRewards,
    ) {}

    /**
     * @param  array<int, array{product_id: int, quantity: int, attributes?: string|null}>  $items
     * @param  array<string, mixed>  $shipping
     */
    public function createPendingOrder(array $items, array $shipping, ?User $user, ?string $promoCode = null): Order
    {
        Log::info('CheckoutService: creating pending order.', [
            'user_id' => $user?->id,
            'item_count' => count($items),
            'shipping' => LogSanitizer::maskShipping($shipping),
        ]);

        $lines = $this->resolveCartLines($items);
        $shippingCents = $this->shippingCalculator->centsForLocation(
            (string) ($shipping['shipping_region'] ?? ''),
            (string) ($shipping['shipping_city'] ?? ''),
        );
        $subtotalCents = (int) $lines->sum('line_total_cents');
        $promoResult = $this->promoCodes->apply($promoCode, $subtotalCents);
        $discountCents = $promoResult['discount_cents'];
        $appliedPromo = $promoResult['promo'];
        $totalCents = max(0, $subtotalCents - $discountCents) + $shippingCents;

        Log::debug('CheckoutService: cart totals calculated.', [
            'line_count' => $lines->count(),
            'subtotal_cents' => $subtotalCents,
            'discount_cents' => $discountCents,
            'promo_code' => $appliedPromo?->code,
            'shipping_cents' => $shippingCents,
            'shipping_region' => $shipping['shipping_region'] ?? null,
            'shipping_city' => $shipping['shipping_city'] ?? null,
            'total_cents' => $totalCents,
        ]);

        if ($totalCents < 1) {
            Log::warning('CheckoutService: rejected — total below minimum.', [
                'total_cents' => $totalCents,
            ]);

            throw ValidationException::withMessages([
                'items' => 'Your cart is empty or invalid.',
            ]);
        }

        return DB::transaction(function () use ($lines, $shipping, $user, $shippingCents, $subtotalCents, $discountCents, $appliedPromo, $totalCents) {
            $reference = $this->generatePaystackReference();
            $orderNumber = $this->generateOrderNumber();

            Log::debug('CheckoutService: persisting order.', [
                'order_number' => $orderNumber,
                'paystack_reference' => $reference,
                'total_cents' => $totalCents,
            ]);

            $order = Order::create([
                'user_id' => $user?->id,
                'order_number' => $orderNumber,
                'status' => OrderStatus::PendingPayment,
                'payment_status' => PaymentStatus::Pending,
                'paystack_reference' => $reference,
                'customer_name' => trim((string) $shipping['customer_name']),
                'customer_email' => strtolower(trim((string) $shipping['customer_email'])),
                'customer_phone' => trim((string) $shipping['customer_phone']),
                'shipping_address_line1' => trim((string) $shipping['shipping_address_line1']),
                'shipping_address_line2' => isset($shipping['shipping_address_line2'])
                    ? trim((string) $shipping['shipping_address_line2']) ?: null
                    : null,
                'shipping_city' => trim((string) $shipping['shipping_city']),
                'shipping_region' => trim((string) $shipping['shipping_region']),
                'shipping_notes' => isset($shipping['shipping_notes'])
                    ? trim((string) $shipping['shipping_notes']) ?: null
                    : null,
                'subtotal_cents' => $subtotalCents,
                'shipping_cents' => $shippingCents,
                'promo_code' => $appliedPromo?->code,
                'discount_cents' => $discountCents,
                'promo_cost_bearer' => $appliedPromo?->cost_bearer,
                'total_cents' => $totalCents,
                'currency' => 'GHS',
            ]);

            foreach ($lines as $line) {
                $order->items()->create($line);

                Log::debug('CheckoutService: order line created.', [
                    'order_id' => $order->id,
                    'product_id' => $line['product_id'],
                    'vendor_user_id' => $line['vendor_user_id'],
                    'quantity' => $line['quantity'],
                    'line_total_cents' => $line['line_total_cents'],
                ]);
            }

            Log::info('CheckoutService: pending order persisted.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'paystack_reference' => $order->paystack_reference,
                'customer_phone_masked' => LogSanitizer::maskPhone($order->customer_phone),
            ]);

            return $order->load('items');
        });
    }

    /**
     * @return array{authorization_url: string, reference: string}
     */
    public function startPaystackPayment(Order $order): array
    {
        Log::info('CheckoutService: starting Paystack payment.', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'paystack_reference' => $order->paystack_reference,
            'total_cents' => $order->total_cents,
            'payment_status' => $order->payment_status->value,
        ]);

        if ($order->isPaid()) {
            Log::warning('CheckoutService: cannot start payment — order already paid.', [
                'order_id' => $order->id,
            ]);

            throw new RuntimeException('This order has already been paid.');
        }

        $callbackUrl = route('checkout.callback', [], true);

        Log::debug('CheckoutService: calling Paystack initialize.', [
            'order_id' => $order->id,
            'callback_url' => $callbackUrl,
            'email_masked' => LogSanitizer::maskEmail($order->customer_email),
            'phone_on_order_masked' => LogSanitizer::maskPhone($order->customer_phone),
            'fields_sent_to_paystack' => ['email', 'amount', 'currency', 'reference', 'callback_url', 'metadata'],
            'fields_not_sent_to_paystack' => ['customer_phone', 'customer_name', 'shipping_address'],
        ]);

        $result = $this->paystack->initializeTransaction(
            email: $order->customer_email,
            amountCents: $order->total_cents,
            reference: $order->paystack_reference,
            callbackUrl: $callbackUrl,
            metadata: [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
        );

        if ($result['authorization_url'] === '') {
            Log::error('CheckoutService: Paystack returned empty authorization URL.', [
                'order_id' => $order->id,
                'paystack_reference' => $order->paystack_reference,
            ]);

            throw new RuntimeException('Paystack did not return a payment URL.');
        }

        Log::info('CheckoutService: Paystack session ready.', [
            'order_id' => $order->id,
            'paystack_reference' => $result['reference'] ?? $order->paystack_reference,
            'access_code_present' => ($result['access_code'] ?? '') !== '',
        ]);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $paystackData
     */
    public function markOrderPaidFromPaystack(Order $order, array $paystackData): Order
    {
        Log::info('CheckoutService: marking order paid from Paystack.', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'current_payment_status' => $order->payment_status->value,
            'paystack_status' => $paystackData['status'] ?? null,
            'paystack_amount' => $paystackData['amount'] ?? null,
            'expected_amount' => $order->total_cents,
            'paystack_reference' => $paystackData['reference'] ?? $order->paystack_reference,
            'paystack_channel' => $paystackData['channel'] ?? null,
        ]);

        if ($order->isPaid()) {
            Log::debug('CheckoutService: order already paid — skipping.', [
                'order_id' => $order->id,
            ]);

            return $order;
        }

        $status = (string) ($paystackData['status'] ?? '');
        $amount = (int) ($paystackData['amount'] ?? 0);

        if ($status !== 'success' || $amount !== $order->total_cents) {
            Log::warning('CheckoutService: payment verification mismatch — marking failed.', [
                'order_id' => $order->id,
                'paystack_status' => $status,
                'paystack_amount' => $amount,
                'expected_amount' => $order->total_cents,
            ]);

            $order->update([
                'status' => OrderStatus::Failed,
                'payment_status' => PaymentStatus::Failed,
            ]);

            throw new RuntimeException('Payment was not successful.');
        }

        return DB::transaction(function () use ($order, $paystackData) {
            $order->refresh();

            if ($order->isPaid()) {
                Log::debug('CheckoutService: order became paid during transaction — skipping.', [
                    'order_id' => $order->id,
                ]);

                return $order;
            }

            $order->update([
                'status' => OrderStatus::Paid,
                'payment_status' => PaymentStatus::Paid,
                'paystack_transaction_id' => isset($paystackData['id']) ? (string) $paystackData['id'] : null,
                'paid_at' => now(),
            ]);

            if ($order->promo_code) {
                $this->promoCodes->incrementUsage($this->promoCodes->findByCode($order->promo_code));
            }

            $this->referralRewards->awardTransactionRewards($order->fresh(['items']));

            Log::info('CheckoutService: order marked paid.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'paystack_transaction_id' => $order->paystack_transaction_id,
            ]);

            Log::info('CheckoutService: dispatching buyer payment SMS job.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_phone_masked' => LogSanitizer::maskPhone($order->customer_phone),
            ]);
            SendOrderPaidSms::dispatch($order->id)->afterCommit();

            $this->notifyVendorsOfPaidOrder($order);

            foreach ($order->items as $item) {
                if ($item->product_id === null) {
                    Log::debug('CheckoutService: skipping stock decrement — no product_id.', [
                        'order_item_id' => $item->id,
                    ]);

                    continue;
                }

                $updated = Product::query()
                    ->whereKey($item->product_id)
                    ->where('stock_quantity', '>=', $item->quantity)
                    ->decrement('stock_quantity', $item->quantity);

                // The bulk decrement bypasses model events, so stamp sold_out_at
                // here when this purchase emptied the stock.
                Product::query()
                    ->whereKey($item->product_id)
                    ->where('stock_quantity', 0)
                    ->whereNull('sold_out_at')
                    ->update(['sold_out_at' => now()]);

                Log::debug('CheckoutService: stock decremented.', [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'rows_affected' => $updated,
                ]);
            }

            return $order->fresh(['items']);
        });
    }

    public function findOrderByReference(string $reference): ?Order
    {
        $order = Order::query()
            ->where('paystack_reference', $reference)
            ->first();

        Log::debug('CheckoutService: find order by reference.', [
            'reference' => $reference,
            'found' => $order !== null,
            'order_id' => $order?->id,
        ]);

        return $order;
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, attributes?: string|null}>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function resolveCartLines(array $items): Collection
    {
        Log::debug('CheckoutService: resolving cart lines.', [
            'raw_item_count' => count($items),
        ]);

        if ($items === []) {
            Log::warning('CheckoutService: cart empty.');

            throw ValidationException::withMessages([
                'items' => 'Your cart is empty.',
            ]);
        }

        $resolved = collect();
        $errors = [];

        foreach ($items as $index => $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $attributes = isset($item['attributes']) ? trim((string) $item['attributes']) : null;

            if ($productId < 1 || $quantity < 1 || $quantity > 99) {
                $errors["items.{$index}"] = 'Invalid cart item.';
                Log::debug('CheckoutService: invalid cart item.', [
                    'index' => $index,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);

                continue;
            }

            $product = Product::query()
                ->visibleInShop()
                ->with(['user.vendorApplication'])
                ->find($productId);

            if ($product === null) {
                $errors["items.{$index}"] = 'A product in your cart is no longer available.';
                Log::debug('CheckoutService: product not available.', [
                    'index' => $index,
                    'product_id' => $productId,
                ]);

                continue;
            }

            if ($product->stock_quantity < $quantity) {
                $errors["items.{$index}"] = "Not enough stock for {$product->title}.";
                Log::debug('CheckoutService: insufficient stock.', [
                    'index' => $index,
                    'product_id' => $productId,
                    'requested' => $quantity,
                    'available' => $product->stock_quantity,
                ]);

                continue;
            }

            $lineTotal = $product->price_cents * $quantity;

            $resolved->push([
                'product_id' => $product->id,
                'vendor_user_id' => $product->user_id,
                'product_title' => $product->title,
                'product_brand' => $product->brand,
                'product_sku' => $product->sku,
                // Store the path, not an absolute URL, so images survive host changes.
                'product_image' => PublicStorageUrl::toStoredPath($product->shopImageUrl()),
                'attributes' => $attributes !== '' ? $attributes : null,
                'unit_price_cents' => $product->price_cents,
                'quantity' => $quantity,
                'line_total_cents' => $lineTotal,
            ]);

            Log::debug('CheckoutService: cart line resolved.', [
                'index' => $index,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price_cents' => $product->price_cents,
                'line_total_cents' => $lineTotal,
            ]);
        }

        if ($errors !== []) {
            Log::warning('CheckoutService: cart validation failed.', [
                'error_count' => count($errors),
                'errors' => $errors,
            ]);

            throw ValidationException::withMessages($errors);
        }

        if ($resolved->isEmpty()) {
            Log::warning('CheckoutService: no valid cart lines after resolution.');

            throw ValidationException::withMessages([
                'items' => 'Your cart is empty.',
            ]);
        }

        $vendorIds = $resolved->pluck('vendor_user_id')->unique()->values();

        if ($vendorIds->count() > 1) {
            Log::warning('CheckoutService: cart spans multiple vendors.', [
                'vendor_ids' => $vendorIds->all(),
            ]);

            throw ValidationException::withMessages([
                'items' => 'Your cart can only include products from one seller. Please remove items from other sellers before checkout.',
            ]);
        }

        return $resolved;
    }

    private function notifyVendorsOfPaidOrder(Order $order): void
    {
        $order->loadMissing(['items.vendor']);

        $order->items
            ->groupBy('vendor_user_id')
            ->each(function ($items, $vendorUserId) use ($order): void {
                /** @var \Illuminate\Support\Collection<int, \App\Models\OrderItem> $items */
                $vendor = $items->first()?->vendor;

                if ($vendor === null) {
                    Log::warning('CheckoutService: skipping vendor notification — vendor missing.', [
                        'order_id' => $order->id,
                        'vendor_user_id' => $vendorUserId,
                    ]);

                    return;
                }

                $vendor->notify(new VendorNewOrderNotification(
                    order: $order,
                    vendorMerchandiseCents: (int) $items->sum('line_total_cents'),
                    itemCount: (int) $items->sum('quantity'),
                ));

                SendVendorNewOrderSms::dispatch($order->id, (int) $vendorUserId)->afterCommit();
            });
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, attributes?: string|null}>  $items
     * @return Collection<int, array<string, mixed>>
     */
    public function previewCartLines(array $items): Collection
    {
        return $this->resolveCartLines($items);
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'LH-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    private function generatePaystackReference(): string
    {
        do {
            $reference = 'LH-'.strtoupper(Str::random(16));
        } while (Order::query()->where('paystack_reference', $reference)->exists());

        return $reference;
    }
}
