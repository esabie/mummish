import Breadcrumbs from '@/Components/Breadcrumbs';
import LogoMark from '@/Components/LogoMark';
import SiteFooter from '@/Components/SiteFooter';
import { Head, Link, usePage } from '@inertiajs/react';

export default function CheckoutFailed({ order }) {
    const { flash } = usePage().props;

    return (
        <>
            <Head title="Payment failed" />

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
                            { label: 'Checkout', href: route('checkout.index') },
                            { label: 'Payment failed' },
                        ]}
                    />

                    <div className="rounded-2xl border border-amber-200 bg-white p-8 shadow-sm">
                        <h1 className="text-2xl font-bold text-stone-900">Payment not completed</h1>
                        <p className="mt-2 text-stone-600">
                            We could not confirm payment for order{' '}
                            <span className="font-semibold text-stone-900">{order.order_number}</span> (
                            {order.formatted_total}).
                        </p>
                        {flash?.error ? (
                            <p className="mt-4 rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                {flash.error}
                            </p>
                        ) : null}
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link
                                href={route('checkout.index')}
                                className="inline-flex rounded-full bg-stone-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-800"
                            >
                                Try again
                            </Link>
                            <Link
                                href={route('shop.index')}
                                className="inline-flex rounded-full border border-stone-300 bg-white px-6 py-3 text-sm font-semibold text-stone-800 transition hover:bg-stone-50"
                            >
                                Back to shop
                            </Link>
                        </div>
                    </div>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
