import { Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import InputError from '@/Components/InputError';
import LogoMark from '@/Components/LogoMark';

export default function SiteFooter() {
    const { auth, canLogin, flash, supportEmail } = usePage().props;
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        name: '',
        phone: '',
    });
    const [joined, setJoined] = useState(false);

    useEffect(() => {
        if (flash?.newsletterJoined) {
            setJoined(true);
            reset();
            clearErrors();
        }
    }, [flash?.newsletterJoined]);

    const submit = (e) => {
        e.preventDefault();
        setJoined(false);
        post(route('newsletter.store'), {
            preserveScroll: true,
        });
    };

    return (
        <footer>
            <div className="border-t border-neutral-200 bg-neutral-100 py-12">
                <div className="mx-auto grid max-w-7xl gap-10 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
                    <div>
                        <LogoMark />
                        <p className="mt-4 text-sm leading-relaxed text-neutral-600">
                            The marketplace for families who want great kids&apos; stuff without the full retail price tag.
                        </p>
                    </div>
                    <div>
                        <h4 className="text-sm font-bold text-neutral-900">Company</h4>
                        <ul className="mt-3 space-y-2 text-sm text-neutral-600">
                            <li>
                                <Link href={route('about')} className="hover:text-market">
                                    About us
                                </Link>
                            </li>
                            <li>
                                <Link href={route('vendor.signup')} className="hover:text-market">
                                    Sell on Mummish
                                </Link>
                            </li>
                            <li>
                                <Link href={route('shipping')} className="hover:text-market">
                                    Shipping &amp; delivery
                                </Link>
                            </li>
                            <li>
                                <Link href={route('orders.track')} className="hover:text-market">
                                    Track order
                                </Link>
                            </li>
                            <li>
                                <Link href={route('contact')} className="hover:text-market">
                                    Contact us
                                </Link>
                            </li>
                            <li>
                                <Link href={route('faq')} className="hover:text-market">
                                    FAQ
                                </Link>
                            </li>
                        </ul>
                    </div>

                    {/* <div>
                        <h4 className="text-sm font-bold text-neutral-900">Shop</h4>
                        <ul className="mt-3 space-y-2 text-sm text-neutral-600">
                            <li>
                                <a href="#" className="hover:text-market">
                                    New arrivals
                                </a>
                            </li>
                            <li>
                                <a href="#" className="hover:text-market">
                                    Best sellers
                                </a>
                            </li>
                            <li>
                                <a href="#" className="hover:text-market">
                                    Under GHS 25
                                </a>
                            </li>
                            <li>
                                <a href="#" className="hover:text-market">
                                    Brands we love
                                </a>
                            </li>
                        </ul>
                    </div> */}

                    <div>
                        <h4 className="text-sm font-bold text-neutral-900">Get the good stuff</h4>
                        <p className="mt-3 text-sm text-neutral-600">
                            Share your number for launches, deals, and seller tips.
                        </p>
                        {joined ? (
                            <p className="mt-3 text-sm font-medium text-emerald-700" role="status">
                                You&apos;re on the list — thanks for joining.
                            </p>
                        ) : (
                            <form className="mt-3 space-y-2" onSubmit={submit}>
                                <div>
                                    <input
                                        type="text"
                                        name="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Name (optional)"
                                        autoComplete="name"
                                        className="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-market focus:outline-none focus:ring-1 focus:ring-market"
                                    />
                                    <InputError message={errors.name} className="mt-1" />
                                </div>
                                <div className="flex gap-2">
                                    <input
                                        type="tel"
                                        name="phone"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        placeholder="Phone number"
                                        autoComplete="tel"
                                        required
                                        className="min-w-0 flex-1 rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-market focus:outline-none focus:ring-1 focus:ring-market"
                                    />
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="rounded-lg bg-market px-4 py-2 text-sm font-semibold text-white hover:bg-market-hover disabled:opacity-60"
                                    >
                                        {processing ? '…' : 'Join'}
                                    </button>
                                </div>
                                <InputError message={errors.phone} />
                            </form>
                        )}
                    </div>
                </div>
            </div>
            <div className="bg-neutral-900 py-6 text-center text-xs text-neutral-400">
                <p>&copy; {new Date().getFullYear()} Mummish. All rights reserved.</p>
                <div className="mt-2 flex flex-wrap justify-center gap-x-4 gap-y-1">
                    <Link href={route('privacy')} className="hover:text-white">
                        Privacy
                    </Link>
                    <Link href={route('terms')} className="hover:text-white">
                        Terms
                    </Link>
                    <Link href={route('billing')} className="hover:text-white">
                        Billing Policy
                    </Link>
                    {canLogin && !auth.user && (
                        <Link href={route('login')} className="hover:text-white">
                            Sign in
                        </Link>
                    )}
                </div>
            </div>
        </footer>
    );
}
