import Breadcrumbs from '@/Components/Breadcrumbs';
import LogoMark from '@/Components/LogoMark';
import SiteFooter from '@/Components/SiteFooter';
import { useCart } from '@/context/CartContext';
import { Head, Link } from '@inertiajs/react';
import { useEffect } from 'react';

export default function CheckoutSuccess({ order }) {
    const { clearCart } = useCart();

    useEffect(() => {
        clearCart();
    }, [clearCart]);

    return (
        <>
            <Head title="Order confirmed" />

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
                            { label: 'Shop', href: route('shop.index') },
                            { label: 'Order confirmed' },
                        ]}
                    />

                    <div className="rounded-2xl border border-emerald-200 bg-white p-8 shadow-sm">
                        <div className="flex items-start gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                <svg className="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold text-stone-900">Thank you for your order!</h1>
                                <p className="mt-2 text-stone-600">
                                    Order <span className="font-semibold text-stone-900">{order.order_number}</span> is
                                    confirmed. Your contact email is{' '}
                                    <span className="font-medium text-stone-800">{order.customer_email}</span> — we will
                                    use it if we need to reach you about this order.
                                </p>
                            </div>
                        </div>

                        <div className="mt-8 grid gap-6 border-t border-stone-100 pt-8 sm:grid-cols-2">
                            <div>
                                <h2 className="text-sm font-bold uppercase tracking-wide text-stone-500">Delivery</h2>
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
                                <h2 className="text-sm font-bold uppercase tracking-wide text-stone-500">Total paid</h2>
                                <p className="mt-2 text-2xl font-bold text-stone-900">{order.formatted_total}</p>
                                {order.formatted_discount ? (
                                    <p className="mt-1 text-sm text-emerald-700">
                                        Includes {order.formatted_discount} off with {order.promo_code}
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

                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link
                                href={route('orders.track', {
                                    order_number: order.order_number,
                                    email: order.customer_email,
                                })}
                                className="inline-flex rounded-full bg-emerald-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800"
                            >
                                Track this order
                            </Link>
                            <Link
                                href={route('shop.index')}
                                className="inline-flex rounded-full bg-stone-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-800"
                            >
                                Continue shopping
                            </Link>
                        </div>
                    </div>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
