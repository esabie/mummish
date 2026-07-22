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

const conditionTiers = [
    {
        title: 'New',
        description:
            'Items that are brand new, unused, unopened, and ideally still have their original tags or packaging attached.',
    },
    {
        title: 'Fairly New',
        description:
            'Pre-loved items showing minimal signs of wear, highly maintained, clean, and in excellent working order.',
    },
    {
        title: 'Used',
        description:
            'Items that have been visibly loved and used by your children, but remain completely functional, safe, and clean for the next family.',
    },
];

const toc = [
    { id: 'registration', label: 'Account registration & safety' },
    { id: 'marketplace', label: 'Marketplace mechanism & service charge' },
    { id: 'vendor', label: 'Vendor commitments & item conditions' },
    { id: 'returns', label: 'Returns and disputes' },
    { id: 'liability', label: 'Limitation of liability' },
];

export default function Terms() {
    const { auth, canLogin, canRegister } = usePage().props;
    const { openCart, count: cartCount } = useCart();

    return (
        <>
            <SeoHead
                title="Terms and Conditions"
                description="Terms and Conditions for using Mummish, Ghana's marketplace for families, in accordance with the Ghanaian Electronic Transactions Act, 2008 (Act 772)."
                url={route('terms')}
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
                                Terms and Conditions
                            </h1>
                            <p className="mt-3 text-sm text-stone-500">Last updated: June 2026</p>
                            <p className="mt-6 text-base leading-relaxed text-stone-600">
                                Welcome to Mummish. By downloading, browsing, or using our platform, you agree to
                                comply with and be bound by these Terms and Conditions. These terms constitute a
                                legally binding agreement between you and Mummish, in strict accordance with the
                                Ghanaian Electronic Transactions Act, 2008 (Act 772).
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
                            <Section id="registration" number="1" title="Account Registration & Safety">
                                <ul className="list-disc space-y-2 pl-5">
                                    <li>
                                        Users must be at least 18 years old, or under the strict supervision of a
                                        parent or legal guardian, to list items.
                                    </li>
                                    <li>
                                        You agree to provide accurate, truthful details about yourself and the items
                                        you list.
                                    </li>
                                </ul>
                            </Section>

                            <Section id="marketplace" number="2" title="Marketplace Mechanism & Service Charge">
                                <p>
                                    Mummish acts strictly as an intermediary marketplace connecting independent
                                    vendors (parents/sellers) with buyers.
                                </p>
                                <div className="rounded-xl border border-[#5c4d3d]/20 bg-[#5c4d3d]/5 p-5">
                                    <p className="font-semibold text-stone-900">The 10% service charge</p>
                                    <p className="mt-2">
                                        For providing the platform, matching audience, security escrow, and customer
                                        support, Mummish applies a flat 10% service fee on the final sale price of
                                        every successfully completed transaction. This fee is automatically deducted
                                        from the payout balance transferred to the vendor.
                                    </p>
                                </div>
                            </Section>

                            <Section id="vendor" number="3" title="Vendor Commitments & Item Conditions">
                                <p>
                                    To maintain absolute trust within the Mummish family, vendors must accurately
                                    categorize the condition of their items using our mandatory tier system:
                                </p>
                                <div className="space-y-4">
                                    {conditionTiers.map(({ title, description }) => (
                                        <div
                                            key={title}
                                            className="rounded-xl border border-stone-200/90 bg-white p-4 shadow-sm"
                                        >
                                            <p className="font-semibold text-stone-900">{title}</p>
                                            <p className="mt-1 text-sm">{description}</p>
                                        </div>
                                    ))}
                                </div>
                                <p className="rounded-xl border border-amber-200/80 bg-amber-50/80 p-4 text-sm text-amber-950">
                                    <span className="font-semibold">Note:</span> Misrepresenting the condition of an
                                    item (e.g., listing a heavily Used item as New) violates our marketplace standards
                                    and may result in a forced refund to the buyer and suspension of your vendor
                                    account. Listing counterfeit goods or unsafe, recalled child safety equipment is
                                    strictly prohibited.
                                </p>
                            </Section>

                            <Section id="returns" number="4" title="Returns and Disputes">
                                <p>
                                    In line with local e-commerce fair trading standards, if an item delivered is
                                    significantly different from its listing description and condition tier, the buyer
                                    must report it within 24 hours of receipt to trigger our dispute resolution
                                    process.
                                </p>
                            </Section>

                            <Section id="liability" number="5" title="Limitation of Liability">
                                <p>
                                    While we vet users and offer secure payment frameworks, Mummish does not physically
                                    own, inspect, or hold the items listed. We are not liable for direct transactions
                                    between users outside our platform. Our third-party delivery service takes over
                                    from there.
                                </p>
                            </Section>
                        </div>

                        <div className="mt-14 rounded-2xl border border-stone-200/90 bg-white p-6 text-center shadow-sm sm:p-8">
                            <p className="text-sm text-stone-600">
                                Questions about these terms?{' '}
                                <a href="#" className="font-semibold text-market hover:underline">
                                    Contact us
                                </a>
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
