import Breadcrumbs from '@/Components/Breadcrumbs';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import LogoMark from '@/Components/LogoMark';
import SiteFooter from '@/Components/SiteFooter';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function TrackOrder({ prefill, error }) {
    const { data, setData, post, processing, errors } = useForm({
        order_number: prefill?.order_number ?? '',
        customer_email: prefill?.customer_email ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('orders.track.lookup'));
    };

    return (
        <>
            <Head title="Track order" />

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
                            { label: 'Track order' },
                        ]}
                    />

                    <div className="rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
                        <h1 className="text-2xl font-bold text-stone-900">Track your order</h1>
                        <p className="mt-2 text-sm leading-relaxed text-stone-600">
                            Enter your order number and the email you used at checkout. You will find your order number
                            on your payment confirmation SMS or receipt.
                        </p>

                        {error ? (
                            <div className="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                                {error}
                            </div>
                        ) : null}

                        <form onSubmit={submit} className="mt-8 space-y-5">
                            <div>
                                <InputLabel htmlFor="order_number" value="Order number" />
                                <TextInput
                                    id="order_number"
                                    name="order_number"
                                    value={data.order_number}
                                    onChange={(e) => setData('order_number', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="e.g. LH-20260717-ABC123"
                                    autoComplete="off"
                                    required
                                />
                                <InputError message={errors.order_number} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="customer_email" value="Email address" />
                                <TextInput
                                    id="customer_email"
                                    type="email"
                                    name="customer_email"
                                    value={data.customer_email}
                                    onChange={(e) => setData('customer_email', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoComplete="email"
                                    required
                                />
                                <InputError message={errors.customer_email} className="mt-2" />
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex rounded-full bg-stone-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-800 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {processing ? 'Looking up…' : 'Track order'}
                            </button>
                        </form>

                        <p className="mt-8 border-t border-stone-100 pt-6 text-sm text-stone-500">
                            Just placed an order?{' '}
                            <Link href={route('shop.index')} className="font-semibold text-[#5c4d3d] hover:underline">
                                Continue shopping
                            </Link>
                        </p>
                    </div>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
