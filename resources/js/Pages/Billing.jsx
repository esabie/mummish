import { Link, usePage } from '@inertiajs/react';
import LogoMark from '@/Components/LogoMark';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';
import { useCart } from '@/context/CartContext';

function IconCart(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.5a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
            />
        </svg>
    );
}

function IconUser(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
            />
        </svg>
    );
}

function Section({ id, number, title, children }) {
    return (
        <section id={id} className="scroll-mt-28 border-b border-stone-200/80 pb-10 last:border-b-0 last:pb-0">
            <div className="flex items-start gap-4">
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#5c4d3d] text-sm font-bold text-white">
                    {number}
                </span>
                <div className="min-w-0 flex-1">
                    <h2 className="text-xl font-bold tracking-tight text-stone-900 sm:text-2xl">{title}</h2>
                    <div className="mt-4 space-y-4 text-base leading-relaxed text-stone-600">{children}</div>
                </div>
            </div>
        </section>
    );
}

const paymentMethods = [
    'Mobile Money (MTN MoMo, Telecel Cash, AT Money)',
    'Local Ghanaian Debit/Credit Cards (Gh-Link, Visa, Mastercard)',
];

const toc = [
    { id: 'payment-methods', label: 'Payment methods' },
    { id: 'commission', label: 'The 20% commission breakdown' },
    { id: 'escrow', label: 'Escrow and payouts' },
];

export default function Billing() {
    const { auth, canLogin, canRegister } = usePage().props;
    const { openCart, count: cartCount } = useCart();

    return (
        <>
            <SeoHead
                title="Billing Policy"
                description="How payments, commissions, escrow, and vendor payouts work on Mummish, Ghana's marketplace for families."
                url={route('billing')}
                image="/images/logo.png"
            />

            <div className="flex min-h-screen flex-col bg-[#faf9f7] text-stone-900 antialiased">
                <header className="sticky top-0 z-40 border-b border-stone-200/90 bg-white/95 backdrop-blur">
                    <div className="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
                        <LogoMark variant="shop" />
                        <nav className="ml-6 hidden gap-6 text-sm font-medium text-stone-700 sm:flex">
                            <Link href={route('shop.index')} className="transition hover:text-market">
                                Shop
                            </Link>
                            <Link href={route('about')} className="transition hover:text-market">
                                About
                            </Link>
                        </nav>
                        <div className="ml-auto flex items-center gap-1 sm:gap-2">
                            {canRegister && (
                                <Link
                                    href={route('vendor.signup')}
                                    className="hidden rounded-full border border-market px-3 py-1.5 text-sm font-semibold text-market transition hover:bg-market-muted sm:inline-block"
                                >
                                    Sell
                                </Link>
                            )}
                            <button
                                type="button"
                                onClick={openCart}
                                className="relative rounded-full p-2 text-stone-700 transition hover:bg-stone-100"
                                aria-label="Shopping bag"
                            >
                                <IconCart className="h-6 w-6" />
                                {cartCount > 0 && (
                                    <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-market px-1 text-[10px] font-bold leading-none text-white">
                                        {cartCount > 99 ? '99+' : cartCount}
                                    </span>
                                )}
                            </button>
                            {auth.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="flex h-9 w-9 items-center justify-center rounded-full bg-stone-200 text-sm font-semibold text-stone-800 transition hover:bg-stone-300"
                                    aria-label="Account"
                                >
                                    {auth.user.name?.charAt(0)?.toUpperCase() ?? '?'}
                                </Link>
                            ) : canLogin ? (
                                <Link
                                    href={route('login')}
                                    className="rounded-full p-2 text-stone-700 transition hover:bg-stone-100"
                                    aria-label="Sign in"
                                >
                                    <IconUser className="h-6 w-6" />
                                </Link>
                            ) : null}
                        </div>
                    </div>
                </header>

                <main className="flex-1">
                    <div className="border-b border-stone-200/80 bg-white">
                        <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
                            <p className="text-xs font-bold uppercase tracking-[0.2em] text-stone-500">Legal</p>
                            <h1 className="mt-2 text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl">
                                Billing Policy
                            </h1>
                            <p className="mt-6 text-base leading-relaxed text-stone-600">
                                This Billing Policy outlines how payments, deductions, and payouts are managed
                                securely on Mummish.
                            </p>
                        </div>
                    </div>

                    <div className="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
                        <nav
                            aria-label="Table of contents"
                            className="mb-12 rounded-2xl border border-stone-200/90 bg-white p-5 shadow-sm sm:p-6"
                        >
                            <p className="text-xs font-bold uppercase tracking-[0.16em] text-stone-500">
                                On this page
                            </p>
                            <ol className="mt-4 space-y-2 text-sm font-medium text-stone-800">
                                {toc.map(({ id, label }, i) => (
                                    <li key={id}>
                                        <a href={`#${id}`} className="inline-flex gap-2 hover:text-market">
                                            <span className="text-stone-400">{i + 1}.</span>
                                            {label}
                                        </a>
                                    </li>
                                ))}
                            </ol>
                        </nav>

                        <div className="space-y-10">
                            <Section id="payment-methods" number="1" title="Payment Methods">
                                <p>
                                    To ensure safety for both parents and vendors, Mummish integrates secure, local
                                    payment options through authorized Ghanaian payment gateways. We accept:
                                </p>
                                <ul className="list-disc space-y-2 pl-5">
                                    {paymentMethods.map((method) => (
                                        <li key={method}>{method}</li>
                                    ))}
                                </ul>
                            </Section>

                            <Section id="commission" number="2" title="The 20% Commission Breakdown">
                                <p>
                                    The service charge covers the administrative and operational costs of running the
                                    marketplace securely.
                                </p>
                                <div className="rounded-xl border border-[#5c4d3d]/20 bg-[#5c4d3d]/5 p-5">
                                    <p className="font-semibold text-stone-900">Example</p>
                                    <p className="mt-2">
                                        If a vendor sells a Fairly New stroller for GHS 500, Mummish&apos;s 20%
                                        commission equals GHS 100. The remaining balance of GHS 400 is released to the
                                        seller&apos;s wallet.
                                    </p>
                                </div>
                                <p>
                                    All prices listed by vendors must be inclusive of any applicable local statutory
                                    levies (such as E-Levy or VAT where legally applicable to commercial sellers).
                                </p>
                            </Section>

                            <Section id="escrow" number="3" title="Escrow and Payouts">
                                <ul className="list-disc space-y-3 pl-5">
                                    <li>
                                        When a buyer pays for an item, the funds are held securely by Mummish in an
                                        escrow layout.
                                    </li>
                                    <li>
                                        Once delivery is confirmed by the buyer or tracked as successfully delivered,
                                        the funds (minus our 20% service fee) are released to the vendor&apos;s wallet.
                                    </li>
                                    <li>
                                        Vendors can withdraw their wallet balances directly to their linked Mobile Money
                                        wallet or bank account within 1 to 3 business days.
                                    </li>
                                </ul>
                            </Section>
                        </div>

                        <div className="mt-14 rounded-2xl border border-stone-200/90 bg-white p-6 text-center shadow-sm sm:p-8">
                            <p className="text-sm text-stone-600">
                                See also our{' '}
                                <Link href={route('terms')} className="font-semibold text-market hover:underline">
                                    Terms and Conditions
                                </Link>
                                .
                            </p>
                            <Link
                                href={route('shop.index')}
                                className="mt-5 inline-flex items-center justify-center rounded-full bg-market px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-market-hover"
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
