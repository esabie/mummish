import Breadcrumbs from '@/Components/Breadcrumbs';
import InputError from '@/Components/InputError';
import LogoMark from '@/Components/LogoMark';
import SearchableCitySelect from '@/Components/SearchableCitySelect';
import SiteFooter from '@/Components/SiteFooter';
import { useCart } from '@/context/CartContext';
import { csrfHeaders } from '@/utils/csrf';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

const inputClass =
    'mt-1.5 block w-full rounded-lg border border-stone-200 bg-white px-3.5 py-2.5 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-market focus:outline-none focus:ring-1 focus:ring-market';

function formatGhs(amount) {
    return `GHS ${(Number(amount) || 0).toFixed(2)}`;
}

function shippingCentsForLocation(region, city, ratesByRegion, ratesByCity) {
    if (!region || !city) {
        return null;
    }

    const cityKey = `${region}|${city}`;

    if (ratesByCity?.[cityKey] != null) {
        return Number(ratesByCity[cityKey]) || 0;
    }

    if (ratesByRegion?.[region] != null) {
        return Number(ratesByRegion[region]) || 0;
    }

    return 0;
}

export default function CheckoutIndex({
    paystackPublicKey,
    shippingRatesByRegion,
    shippingRatesByCity,
    ghanaRegions,
    ghanaCitiesByRegion,
    customer,
}) {
    const { lines, subtotal } = useCart();
    const { flash } = usePage().props;

    const { data, setData, post, processing, errors, transform } = useForm({
        items: [],
        customer_name: customer?.name ?? '',
        customer_email: customer?.email ?? '',
        customer_phone: '',
        shipping_address_line1: '',
        shipping_address_line2: '',
        shipping_city: '',
        shipping_region: '',
        shipping_notes: '',
        promo_code: '',
    });

    const [promoInput, setPromoInput] = useState('');
    const [appliedPromo, setAppliedPromo] = useState(null);
    const [promoError, setPromoError] = useState(null);
    const [promoLoading, setPromoLoading] = useState(false);

    const cartPayload = useMemo(
        () =>
            lines.map((line) => ({
                product_id: line.productId,
                quantity: line.qty,
                attributes: line.attributes || null,
            })),
        [lines]
    );

    useEffect(() => {
        setData('items', cartPayload);
    }, [cartPayload, setData]);

    const submit = (e) => {
        e.preventDefault();

        transform((formData) => ({
            ...formData,
            items: cartPayload,
        }));

        post(route('checkout.store'), {
            preserveScroll: true,
            onFinish: () => {
                transform((formData) => formData);
            },
        });
    };

    const hasErrors = Object.keys(errors).length > 0;

    const citiesForRegion = useMemo(
        () => (data.shipping_region ? ghanaCitiesByRegion?.[data.shipping_region] ?? [] : []),
        [data.shipping_region, ghanaCitiesByRegion]
    );

    const shippingCents = useMemo(
        () =>
            shippingCentsForLocation(
                data.shipping_region,
                data.shipping_city,
                shippingRatesByRegion,
                shippingRatesByCity
            ),
        [data.shipping_region, data.shipping_city, shippingRatesByRegion, shippingRatesByCity]
    );

    const shippingAmount = shippingCents == null ? null : shippingCents / 100;
    const discountAmount = (appliedPromo?.discount_cents ?? 0) / 100;
    const total = Math.max(0, subtotal - discountAmount) + (shippingAmount ?? 0);

    const applyPromoCode = async () => {
        const code = promoInput.trim();

        if (!code) {
            setPromoError('Enter a promo code.');
            return;
        }

        setPromoLoading(true);
        setPromoError(null);

        try {
            const response = await fetch(route('checkout.promo.validate'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    ...csrfHeaders(),
                },
                body: JSON.stringify({
                    promo_code: code,
                    items: cartPayload,
                }),
            });

            if (response.status === 419) {
                setPromoError('Your session expired. Refresh the page and try again.');
                return;
            }

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message =
                    payload?.errors?.promo_code?.[0] ??
                    payload?.message ??
                    'This promo code could not be applied.';
                setAppliedPromo(null);
                setData('promo_code', '');
                setPromoError(message);
                return;
            }

            setAppliedPromo(payload);
            setData('promo_code', payload.promo_code);
            setPromoInput(payload.promo_code);
        } catch {
            setPromoError('Could not validate promo code. Please try again.');
        } finally {
            setPromoLoading(false);
        }
    };

    const clearPromoCode = () => {
        setPromoInput('');
        setAppliedPromo(null);
        setPromoError(null);
        setData('promo_code', '');
    };

    useEffect(() => {
        if (lines.length === 0) {
            clearPromoCode();
        }
    }, [lines.length]);

    const handleRegionChange = (region) => {
        setData({
            ...data,
            shipping_region: region,
            shipping_city: '',
        });
    };

    if (!paystackPublicKey) {
        return null;
    }

    return (
        <>
            <Head title="Checkout" />

            <div className="min-h-screen bg-stone-50 text-stone-900">
                <header className="border-b border-stone-200 bg-white">
                    <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
                        <LogoMark variant="shop" />
                        <Link href={route('shop.index')} className="text-sm font-medium text-market hover:underline">
                            Continue shopping
                        </Link>
                    </div>
                </header>

                <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6">
                    <Breadcrumbs
                        tone="shop"
                        className="mb-6"
                        items={[
                            { label: 'Home', href: '/' },
                            { label: 'Shop', href: route('shop.index') },
                            { label: 'Checkout' },
                        ]}
                    />

                    {flash?.error && (
                        <div className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                            {flash.error}
                        </div>
                    )}

                    {hasErrors && (
                        <div className="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                            Please fix the highlighted fields below before paying.
                        </div>
                    )}

                    {lines.length === 0 ? (
                        <div className="rounded-2xl border border-stone-200 bg-white p-10 text-center shadow-sm">
                            <h1 className="text-2xl font-bold text-stone-900">Your cart is empty</h1>
                            <p className="mt-2 text-stone-600">Add items from the shop before checking out.</p>
                            <Link
                                href={route('shop.index')}
                                className="mt-6 inline-flex rounded-full bg-stone-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-800"
                            >
                                Browse shop
                            </Link>
                        </div>
                    ) : (
                        <form onSubmit={submit} className="grid gap-8 lg:grid-cols-[1fr_380px]">
                            <div className="space-y-6">
                                <section className="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                                    <h2 className="text-lg font-bold text-stone-900">Contact</h2>
                                    <div className="mt-5 grid gap-4 sm:grid-cols-2">
                                        <div className="sm:col-span-2">
                                            <label htmlFor="customer_name" className="text-sm font-medium text-stone-700">
                                                Full name
                                            </label>
                                            <input
                                                id="customer_name"
                                                type="text"
                                                value={data.customer_name}
                                                onChange={(e) => setData('customer_name', e.target.value)}
                                                className={inputClass}
                                                required
                                            />
                                            <InputError message={errors.customer_name} className="mt-1" />
                                        </div>
                                        <div>
                                            <label htmlFor="customer_email" className="text-sm font-medium text-stone-700">
                                                Email
                                            </label>
                                            <input
                                                id="customer_email"
                                                type="email"
                                                value={data.customer_email}
                                                onChange={(e) => setData('customer_email', e.target.value)}
                                                className={inputClass}
                                                required
                                            />
                                            <InputError message={errors.customer_email} className="mt-1" />
                                        </div>
                                        <div>
                                            <label htmlFor="customer_phone" className="text-sm font-medium text-stone-700">
                                                Phone
                                            </label>
                                            <input
                                                id="customer_phone"
                                                type="tel"
                                                value={data.customer_phone}
                                                onChange={(e) => setData('customer_phone', e.target.value)}
                                                className={inputClass}
                                                placeholder="e.g. 024 123 4567"
                                                required
                                            />
                                            <InputError message={errors.customer_phone} className="mt-1" />
                                        </div>
                                    </div>
                                </section>

                                <section className="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                                    <h2 className="text-lg font-bold text-stone-900">Delivery address</h2>
                                    <div className="mt-5 grid gap-4 sm:grid-cols-2">
                                        <div className="sm:col-span-2">
                                            <label htmlFor="shipping_address_line1" className="text-sm font-medium text-stone-700">
                                                Street address
                                            </label>
                                            <input
                                                id="shipping_address_line1"
                                                type="text"
                                                value={data.shipping_address_line1}
                                                onChange={(e) => setData('shipping_address_line1', e.target.value)}
                                                className={inputClass}
                                                required
                                            />
                                            <InputError message={errors.shipping_address_line1} className="mt-1" />
                                        </div>
                                        <div className="sm:col-span-2">
                                            <label htmlFor="shipping_address_line2" className="text-sm font-medium text-stone-700">
                                                Apartment, suite, etc. (optional)
                                            </label>
                                            <input
                                                id="shipping_address_line2"
                                                type="text"
                                                value={data.shipping_address_line2}
                                                onChange={(e) => setData('shipping_address_line2', e.target.value)}
                                                className={inputClass}
                                            />
                                            <InputError message={errors.shipping_address_line2} className="mt-1" />
                                        </div>
                                        <div>
                                            <label htmlFor="shipping_region" className="text-sm font-medium text-stone-700">
                                                Region
                                            </label>
                                            <select
                                                id="shipping_region"
                                                value={data.shipping_region}
                                                onChange={(e) => handleRegionChange(e.target.value)}
                                                className={inputClass}
                                                required
                                            >
                                                <option value="">Select region</option>
                                                {ghanaRegions.map((region) => (
                                                    <option key={region} value={region}>
                                                        {region}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors.shipping_region} className="mt-1" />
                                        </div>
                                        <div>
                                            <label htmlFor="shipping_city" className="text-sm font-medium text-stone-700">
                                                City
                                            </label>
                                            <SearchableCitySelect
                                                id="shipping_city"
                                                value={data.shipping_city}
                                                onChange={(city) => setData('shipping_city', city)}
                                                cities={citiesForRegion}
                                                region={data.shipping_region}
                                                className={inputClass}
                                                required
                                                placeholder="Select city"
                                                searchPlaceholder="Search city..."
                                            />
                                            <InputError message={errors.shipping_city} className="mt-1" />
                                        </div>
                                        <div className="sm:col-span-2">
                                            <label htmlFor="shipping_notes" className="text-sm font-medium text-stone-700">
                                                Delivery notes (optional)
                                            </label>
                                            <textarea
                                                id="shipping_notes"
                                                value={data.shipping_notes}
                                                onChange={(e) => setData('shipping_notes', e.target.value)}
                                                rows={3}
                                                className={`${inputClass} resize-y`}
                                                placeholder="e.g. Place at the door, handle with care ..."
                                            />
                                            <InputError message={errors.shipping_notes} className="mt-1" />
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <aside className="lg:sticky lg:top-8 lg:self-start">
                                <div className="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                                    <h2 className="text-lg font-bold text-stone-900">Order summary</h2>
                                    <ul className="mt-5 space-y-4 border-b border-stone-100 pb-5">
                                        {lines.map((line) => (
                                            <li key={line.lineId} className="flex gap-3">
                                                <div className="h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-stone-200 bg-stone-50">
                                                    <img src={line.image} alt="" className="h-full w-full object-cover" />
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <p className="text-sm font-semibold text-stone-900">{line.name}</p>
                                                    {line.attributes ? (
                                                        <p className="mt-0.5 text-xs text-stone-500">{line.attributes}</p>
                                                    ) : null}
                                                    <p className="mt-1 text-xs text-stone-500">Qty {line.qty}</p>
                                                </div>
                                                <p className="text-sm font-semibold text-stone-900">
                                                    {formatGhs((line.priceAmount || 0) * line.qty)}
                                                </p>
                                            </li>
                                        ))}
                                    </ul>

                                    <dl className="mt-5 space-y-2 text-sm">
                                        <div className="flex justify-between">
                                            <dt className="text-stone-600">Subtotal</dt>
                                            <dd className="font-semibold text-stone-900">{formatGhs(subtotal)}</dd>
                                        </div>
                                        {discountAmount > 0 && (
                                            <div className="flex justify-between text-emerald-700">
                                                <dt>
                                                    Promo
                                                    {appliedPromo?.promo_code ? (
                                                        <span className="mt-0.5 block text-xs font-normal text-emerald-600">
                                                            {appliedPromo.promo_code}
                                                            {appliedPromo.discount_label
                                                                ? ` · ${appliedPromo.discount_label}`
                                                                : ''}
                                                        </span>
                                                    ) : null}
                                                </dt>
                                                <dd className="font-semibold">-{formatGhs(discountAmount)}</dd>
                                            </div>
                                        )}
                                        <div className="flex justify-between">
                                            <dt className="text-stone-600">
                                                Shipping
                                                {data.shipping_city ? (
                                                    <span className="mt-0.5 block text-xs font-normal text-stone-500">
                                                        to {data.shipping_city}
                                                        {data.shipping_region ? `, ${data.shipping_region}` : ''}
                                                    </span>
                                                ) : null}
                                            </dt>
                                            <dd className="font-semibold text-stone-900">
                                                {shippingAmount == null
                                                    ? 'Select city'
                                                    : shippingAmount > 0
                                                      ? formatGhs(shippingAmount)
                                                      : 'Free'}
                                            </dd>
                                        </div>
                                        <div className="flex justify-between border-t border-stone-100 pt-3 text-base">
                                            <dt className="font-bold text-stone-900">Total</dt>
                                            <dd className="font-bold text-stone-900">{formatGhs(total)}</dd>
                                        </div>
                                    </dl>

                                    <div className="mt-5 border-t border-stone-100 pt-5">
                                        <label htmlFor="promo_code" className="text-sm font-medium text-stone-700">
                                            Promo code
                                        </label>
                                        <div className="mt-2 flex gap-2">
                                            <input
                                                id="promo_code"
                                                type="text"
                                                value={promoInput}
                                                onChange={(e) => {
                                                    setPromoInput(e.target.value.toUpperCase());
                                                    if (promoError) {
                                                        setPromoError(null);
                                                    }
                                                }}
                                                className={`${inputClass} mt-0 flex-1 uppercase`}
                                                placeholder="Enter Promo Code"
                                                autoComplete="off"
                                            />
                                            {appliedPromo ? (
                                                <button
                                                    type="button"
                                                    onClick={clearPromoCode}
                                                    className="shrink-0 rounded-lg border border-stone-200 px-3 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-50"
                                                >
                                                    Remove
                                                </button>
                                            ) : (
                                                <button
                                                    type="button"
                                                    onClick={applyPromoCode}
                                                    disabled={promoLoading || lines.length === 0}
                                                    className="shrink-0 rounded-lg bg-stone-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-stone-800 disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    {promoLoading ? 'Checking…' : 'Apply'}
                                                </button>
                                            )}
                                        </div>
                                        {promoError ? (
                                            <p className="mt-2 text-sm text-red-600">{promoError}</p>
                                        ) : null}
                                        <InputError message={errors.promo_code} className="mt-2" />
                                    </div>

                                    <InputError message={errors.items} className="mt-4" />

                                    <button
                                        type="submit"
                                        disabled={processing || lines.length === 0}
                                        className="mt-6 w-full rounded-full bg-stone-900 py-3.5 text-sm font-bold uppercase tracking-wider text-white transition hover:bg-stone-800 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {processing ? 'Redirecting to Paystack…' : 'Proceed to Pay'}
                                    </button>

                                    <p className="mt-3 text-center text-xs text-stone-500">
                                        You will be redirected to Paystack to complete payment securely.
                                    </p>
                                </div>
                            </aside>
                        </form>
                    )}
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
