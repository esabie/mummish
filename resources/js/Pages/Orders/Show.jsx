import Breadcrumbs from '@/Components/Breadcrumbs';
import LogoMark from '@/Components/LogoMark';
import SiteFooter from '@/Components/SiteFooter';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

function formatDateTime(iso) {
    if (!iso) {
        return null;
    }

    try {
        return new Date(iso).toLocaleString(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    } catch {
        return null;
    }
}

function TimelineIcon({ status }) {
    if (status === 'complete') {
        return (
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        );
    }

    if (status === 'current') {
        return (
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-emerald-500 bg-white">
                <span className="h-2.5 w-2.5 rounded-full bg-emerald-500" />
            </div>
        );
    }

    return (
        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-stone-200 bg-stone-50">
            <span className="h-2 w-2 rounded-full bg-stone-300" />
        </div>
    );
}

export default function OrderShow({ order, status, error }) {
    const [confirming, setConfirming] = useState(false);

    const confirmReceipt = () => {
        if (confirming) {
            return;
        }

        setConfirming(true);
        router.post(route('orders.track.received', order.id), {}, {
            preserveScroll: true,
            onFinish: () => setConfirming(false),
        });
    };

    return (
        <>
            <Head title={`Order ${order.order_number}`} />

            <div className="min-h-screen bg-stone-50 text-stone-900">
                <header className="border-b border-stone-200 bg-white">
                    <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-4 sm:px-6">
                        <LogoMark variant="shop" />
                    </div>
                </header>

                <main className="mx-auto max-w-4xl px-4 py-8 sm:px-6">
                    <Breadcrumbs
                        tone="shop"
                        className="mb-6"
                        items={[
                            { label: 'Home', href: '/' },
                            { label: 'Track order', href: route('orders.track') },
                            { label: order.order_number },
                        ]}
                    />

                    <div className="rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p className="text-xs font-bold uppercase tracking-wide text-stone-500">Order status</p>
                                <h1 className="mt-1 text-2xl font-bold text-stone-900">{order.current_step_label}</h1>
                                <p className="mt-2 text-sm text-stone-600">
                                    Order <span className="font-semibold text-stone-900">{order.order_number}</span>
                                    {order.shop_name ? (
                                        <>
                                            {' '}
                                            from <span className="font-medium text-stone-800">{order.shop_name}</span>
                                        </>
                                    ) : null}
                                </p>
                                {order.placed_at ? (
                                    <p className="mt-1 text-xs text-stone-500">Placed {formatDateTime(order.placed_at)}</p>
                                ) : null}
                            </div>
                            <div className="rounded-xl bg-stone-50 px-4 py-3 text-right">
                                <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Total</p>
                                <p className="mt-1 text-xl font-bold text-stone-900">{order.formatted_total}</p>
                            </div>
                        </div>

                        {status ? (
                            <div className="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                                {status}
                            </div>
                        ) : null}

                        {error ? (
                            <div className="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                                {error}
                            </div>
                        ) : null}

                        <div className="mt-8 border-t border-stone-100 pt-8">
                            <h2 className="text-sm font-bold uppercase tracking-wide text-stone-500">Delivery progress</h2>
                            <ol className="mt-6 space-y-0">
                                {order.timeline.map((step, index) => (
                                    <li key={step.key} className="relative flex gap-4 pb-8 last:pb-0">
                                        {index < order.timeline.length - 1 ? (
                                            <span
                                                className={`absolute left-4 top-8 -ml-px h-[calc(100%-1rem)] w-0.5 ${
                                                    step.status === 'complete' ? 'bg-emerald-200' : 'bg-stone-200'
                                                }`}
                                                aria-hidden
                                            />
                                        ) : null}
                                        <TimelineIcon status={step.status} />
                                        <div className="min-w-0 flex-1 pt-0.5">
                                            <p
                                                className={`text-sm font-semibold ${
                                                    step.status === 'upcoming' ? 'text-stone-400' : 'text-stone-900'
                                                }`}
                                            >
                                                {step.label}
                                            </p>
                                            <p
                                                className={`mt-0.5 text-sm ${
                                                    step.status === 'upcoming' ? 'text-stone-400' : 'text-stone-600'
                                                }`}
                                            >
                                                {step.description}
                                            </p>
                                            {step.completed_at ? (
                                                <p className="mt-1 text-xs text-stone-500">
                                                    {formatDateTime(step.completed_at)}
                                                </p>
                                            ) : null}
                                        </div>
                                    </li>
                                ))}
                            </ol>

                            {order.can_confirm_receipt ? (
                                <div className="mt-2 rounded-xl border border-emerald-200 bg-emerald-50/80 p-5">
                                    <p className="text-sm font-semibold text-emerald-950">Got your order?</p>
                                    <p className="mt-1 text-sm text-emerald-900/80">
                                        Confirm when it arrives so we can release payment to the seller.
                                    </p>
                                    <button
                                        type="button"
                                        onClick={confirmReceipt}
                                        disabled={confirming}
                                        className="mt-4 inline-flex rounded-full bg-emerald-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        {confirming ? 'Confirming…' : "I've received my order"}
                                    </button>
                                </div>
                            ) : null}

                            {order.is_delivered ? (
                                <div className="mt-2 rounded-xl border border-emerald-200 bg-emerald-50/80 px-5 py-4 text-sm text-emerald-900">
                                    Delivery confirmed. Thanks for shopping with Mummish.
                                </div>
                            ) : null}
                        </div>

                        <div className="mt-8 grid gap-6 border-t border-stone-100 pt-8 sm:grid-cols-2">
                            <div>
                                <h2 className="text-sm font-bold uppercase tracking-wide text-stone-500">Delivery to</h2>
                                <p className="mt-2 text-sm leading-relaxed text-stone-700">
                                    {order.customer_name}
                                    <br />
                                    {order.shipping_address_line1}
                                    {order.shipping_address_line2 ? (
                                        <>
                                            <br />
                                            {order.shipping_address_line2}
                                        </>
                                    ) : null}
                                    <br />
                                    {order.shipping_city}, {order.shipping_region}
                                </p>
                            </div>
                            <div>
                                <h2 className="text-sm font-bold uppercase tracking-wide text-stone-500">Contact</h2>
                                <p className="mt-2 text-sm text-stone-700">{order.customer_email}</p>
                                {order.formatted_discount ? (
                                    <p className="mt-3 text-sm text-emerald-700">
                                        {order.formatted_discount} off with {order.promo_code}
                                    </p>
                                ) : null}
                            </div>
                        </div>

                        <ul className="mt-8 space-y-4 border-t border-stone-100 pt-8">
                            {order.items.map((item, index) => (
                                <li key={`${item.title}-${index}`} className="flex gap-4">
                                    <div className="h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-stone-200 bg-stone-50">
                                        {item.image ? (
                                            <img src={item.image} alt="" className="h-full w-full object-cover" />
                                        ) : null}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="font-semibold text-stone-900">{item.title}</p>
                                        {item.brand ? <p className="text-xs text-stone-500">{item.brand}</p> : null}
                                        {item.attributes ? (
                                            <p className="mt-0.5 text-xs text-stone-500">{item.attributes}</p>
                                        ) : null}
                                        <p className="mt-1 text-sm text-stone-600">Qty {item.quantity}</p>
                                    </div>
                                    <p className="text-sm font-semibold text-stone-900">{item.formatted_line_total}</p>
                                </li>
                            ))}
                        </ul>

                        <div className="mt-8 flex flex-wrap gap-3 border-t border-stone-100 pt-8">
                            <Link
                                href={route('shop.index')}
                                className="inline-flex rounded-full bg-stone-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-800"
                            >
                                Continue shopping
                            </Link>
                            <Link
                                href={route('orders.track')}
                                className="inline-flex rounded-full border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-800 transition hover:bg-stone-50"
                            >
                                Track another order
                            </Link>
                        </div>
                    </div>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
