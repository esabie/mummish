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

function IconCheck(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
    );
}

function IconTruck(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.038v-.591c0-.598.237-1.17.659-1.591L15.793 3.5A2.25 2.25 0 0014.207 2.875H11.25v11.25"
            />
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M3 14.25h6.75V5.25H3.375A1.125 1.125 0 002.25 6.375v6.75c0 .621.504 1.125 1.125 1.125H3z"
            />
        </svg>
    );
}

function IconMap(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"
            />
        </svg>
    );
}

function IconGlobe(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"
            />
        </svg>
    );
}

const deliveryMethods = [
    {
        title: 'Accra delivery',
        subtitle: 'Greater Accra local',
        icon: IconTruck,
        accent: 'bg-[#c3e9fa]/70 text-[#5c4d3d]',
        points: [
            'Courier delivery within Accra and nearby suburbs',
            'Zone-based pricing shown accurately at checkout',
            'Rider confirmation call before delivery',
            'Typical delivery: 1–5 working days',
            'Clear address and landmark help a smooth drop-off',
        ],
    },
    {
        title: 'Metro hubs',
        subtitle: 'Kumasi & Takoradi',
        icon: IconMap,
        accent: 'bg-emerald-100 text-emerald-800',
        points: [
            'Local courier coverage in major metro areas',
            'City rates take priority when your town is listed',
            'Reliable handling through our delivery partners',
            'Timing confirmed at checkout for your location',
            'Track progress after your order is confirmed',
        ],
    },
    {
        title: 'Nationwide Ghana',
        subtitle: 'All other regions',
        icon: IconGlobe,
        accent: 'bg-amber-100 text-amber-900',
        points: [
            'Delivery across Ghana’s regions via courier partners',
            'Intercity rates based on region and city',
            'Working-day timing shared at checkout',
            'Secure packaging for family essentials',
            'Signature or phone confirmation where available',
        ],
    },
];

const deliveryTimes = [
    { label: 'Accra', value: '1–5 working days' },
    { label: 'Metro hubs', value: '1–5 working days' },
    { label: 'Other regions', value: 'Confirmed at checkout' },
    { label: 'International', value: 'Not available yet' },
];

const importantNotes = [
    'Shipping is currently only available in Ghana.',
    'Orders placed after 5pm GMT begin processing the next business day.',
    'Working-day durations exclude weekends and public holidays.',
    'Shipping cost is calculated at checkout from your delivery region and city.',
    'Delivery times can vary by seller location and courier capacity.',
];

export default function Shipping() {
    const { auth, canLogin, canRegister, supportEmail } = usePage().props;
    const { openCart, count: cartCount } = useCart();

    return (
        <>
            <SeoHead
                title="Shipping & Delivery"
                description="Shipping on Mummish is available across Ghana. See Accra, metro, and nationwide delivery times, zone-based pricing, and how to track your order."
                url="/shipping"
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
                            <span className="font-semibold text-market">Shipping</span>
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
                    <div className="border-b border-amber-200/80 bg-amber-50">
                        <p className="mx-auto max-w-7xl px-4 py-2.5 text-center text-sm font-medium text-amber-950 sm:px-6 lg:px-8">
                            Shipping currently only available in Ghana.
                        </p>
                    </div>

                    <section className="relative overflow-hidden border-b border-stone-200/80 bg-[#c3e9fa]">
                        <div
                            className="pointer-events-none absolute inset-0 opacity-40 motion-safe:animate-[pulse_8s_ease-in-out_infinite]"
                            aria-hidden
                            style={{
                                backgroundImage:
                                    'radial-gradient(circle at 18% 75%, rgba(255,255,255,0.85) 0%, transparent 48%), radial-gradient(circle at 82% 18%, rgba(255,255,255,0.55) 0%, transparent 42%)',
                            }}
                        />
                        <div className="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
                            <Link
                                href={route('home')}
                                className="inline-flex items-center gap-1.5 text-sm font-medium text-[#5c4d3d]/80 transition hover:text-[#5c4d3d]"
                            >
                                <span aria-hidden>←</span> Back to home
                            </Link>
                            <p className="mt-6 text-xs font-bold uppercase tracking-[0.2em] text-[#5c4d3d]/70">
                                Shipping &amp; delivery
                            </p>
                            <h1 className="mt-3 max-w-3xl text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl lg:text-5xl">
                                Delivery across Ghana, priced for your location
                            </h1>
                            <p className="mt-5 max-w-2xl text-base leading-relaxed text-stone-700 sm:text-lg">
                                We deliver family essentials with care. Choose your region at checkout for zone-based
                                rates, then track your order from confirmation to doorstep.
                            </p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <Link
                                    href={route('shop.index')}
                                    className="inline-flex items-center justify-center rounded-full bg-[#5c4d3d] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#4a3e32]"
                                >
                                    Browse the shop
                                </Link>
                                <Link
                                    href={route('orders.track')}
                                    className="inline-flex items-center justify-center rounded-full border-2 border-[#5c4d3d] bg-white/80 px-6 py-3 text-sm font-semibold text-[#5c4d3d] transition hover:bg-white"
                                >
                                    Track an order
                                </Link>
                            </div>
                        </div>
                    </section>

                    <section className="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
                        <div className="max-w-2xl">
                            <h2 className="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">
                                How delivery works
                            </h2>
                            <p className="mt-4 text-base leading-relaxed text-stone-600">
                                Rates depend on where you are in Ghana. Accra, metro hubs, and other regions each use
                                their own courier pricing. Rates are always confirmed before you pay.
                            </p>
                        </div>

                        <div className="mt-10 grid gap-5 lg:grid-cols-3">
                            {deliveryMethods.map(({ title, subtitle, icon: Icon, accent, points }, index) => (
                                <article
                                    key={title}
                                    className="group rounded-2xl border border-stone-200/90 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-md sm:p-7"
                                    style={{ animationDelay: `${index * 80}ms` }}
                                >
                                    <div
                                        className={`flex h-11 w-11 items-center justify-center rounded-xl transition group-hover:scale-105 ${accent}`}
                                    >
                                        <Icon className="h-5 w-5" />
                                    </div>
                                    <p className="mt-5 text-xs font-bold uppercase tracking-[0.16em] text-stone-500">
                                        {subtitle}
                                    </p>
                                    <h3 className="mt-1.5 text-xl font-bold text-stone-900">{title}</h3>
                                    <ul className="mt-5 space-y-3">
                                        {points.map((point) => (
                                            <li key={point} className="flex items-start gap-2.5 text-sm leading-relaxed text-stone-600">
                                                <IconCheck
                                                    className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600"
                                                    aria-hidden
                                                />
                                                <span>{point}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </article>
                            ))}
                        </div>
                    </section>

                    <section className="border-y border-stone-200/80 bg-white">
                        <div className="mx-auto grid max-w-7xl gap-8 px-4 py-14 sm:px-6 sm:py-16 lg:grid-cols-3 lg:gap-10 lg:px-8">
                            <div>
                                <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#5c4d3d]/70">
                                    Processing time
                                </p>
                                <p className="mt-3 text-3xl font-bold tracking-tight text-stone-900">1–2 days</p>
                                <p className="mt-3 text-sm leading-relaxed text-stone-600">
                                    Orders are prepared within 1–2 business days before they hand off to our courier
                                    partners.
                                </p>
                            </div>

                            <div>
                                <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#5c4d3d]/70">
                                    Delivery times
                                </p>
                                <ul className="mt-4 space-y-3">
                                    {deliveryTimes.map(({ label, value }) => (
                                        <li
                                            key={label}
                                            className="flex items-baseline justify-between gap-4 border-b border-stone-100 pb-2 text-sm last:border-b-0"
                                        >
                                            <span className="font-medium text-stone-700">{label}</span>
                                            <span className="text-right text-stone-500">{value}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div>
                                <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#5c4d3d]/70">
                                    Important
                                </p>
                                <ul className="mt-4 space-y-3">
                                    {importantNotes.map((note) => (
                                        <li key={note} className="flex items-start gap-2.5 text-sm leading-relaxed text-stone-600">
                                            <span
                                                className="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-[#5c4d3d]"
                                                aria-hidden
                                            />
                                            <span>{note}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section className="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-16 lg:px-8">
                        <div className="overflow-hidden rounded-3xl bg-[#5c4d3d] text-white shadow-lg">
                            <div className="grid lg:grid-cols-5">
                                <div className="p-8 sm:p-10 lg:col-span-2 lg:p-12">
                                    <p className="text-xs font-bold uppercase tracking-[0.2em] text-white/60">
                                        Need help?
                                    </p>
                                    <h2 className="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">
                                        Questions about your delivery?
                                    </h2>
                                </div>
                                <div className="flex flex-col justify-center gap-4 border-t border-white/10 p-8 sm:p-10 lg:col-span-3 lg:border-l lg:border-t-0 lg:p-12">
                                    <p className="text-base leading-relaxed text-white/90">
                                        Track your package anytime, or reach out if something looks off with your
                                        address, timing, or order status.
                                    </p>
                                    <div className="flex flex-wrap gap-3">
                                        <Link
                                            href={route('orders.track')}
                                            className="inline-flex items-center justify-center rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#5c4d3d] transition hover:bg-white/90"
                                        >
                                            Track order
                                        </Link>
                                        <a
                                            href={`mailto:${supportEmail}`}
                                            className="inline-flex items-center justify-center rounded-full border border-white/40 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10"
                                        >
                                            Email support
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="border-t border-stone-200/80 bg-white">
                        <div className="mx-auto max-w-7xl px-4 py-14 text-center sm:px-6 sm:py-16 lg:px-8">
                            <h2 className="text-xl font-bold text-stone-900 sm:text-2xl">
                                Nationwide Delivery
                            </h2>
                            <p className="mx-auto mt-3 max-w-lg text-sm text-stone-600 sm:text-base">
                                Shop family finds with confidence. Your shipping total is always shown before you pay,
                                and you can follow every step after checkout. Track your package every step of the way.
                            </p>
                            <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                                <Link
                                    href={route('shop.index')}
                                    className="inline-flex items-center justify-center rounded-full bg-market px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-market-hover"
                                >
                                    Shop now
                                </Link>
                                <Link
                                    href={route('orders.track')}
                                    className="inline-flex items-center justify-center rounded-full border-2 border-market px-8 py-3 text-sm font-semibold text-market transition hover:bg-market-muted"
                                >
                                    Track package
                                </Link>
                            </div>
                        </div>
                    </section>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
