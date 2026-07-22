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

function ContactCard({ title, subtitle, href, label, external = false }) {
    return (
        <a
            href={href}
            {...(external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
            className="rounded-2xl border border-stone-200/90 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-[#5c4d3d]/35 hover:shadow-md"
        >
            <p className="text-xs font-bold uppercase tracking-[0.16em] text-stone-500">{subtitle}</p>
            <h3 className="mt-1.5 text-xl font-bold text-stone-900">{title}</h3>
            <p className="mt-4 text-sm font-semibold text-[#5c4d3d] underline">{label}</p>
        </a>
    );
}

export default function Contact() {
    const { auth, canLogin, canRegister, supportEmail, supportPhone, supportWhatsAppUrl } = usePage().props;
    const { openCart, count: cartCount } = useCart();

    return (
        <>
            <SeoHead
                title="Contact Us"
                description="Need help with Mummish? Contact our support team by email, WhatsApp, or phone and we will help you quickly."
                url="/contact"
                image="/images/logo.png"
            />

            <div className="flex min-h-screen flex-col bg-[#faf9f7] text-stone-900 antialiased">
                <header className="sticky top-0 z-40 border-b border-stone-200/90 bg-white/95 backdrop-blur">
                    <div className="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
                        <LogoMark variant="shop" />
                        <nav className="ml-6 hidden gap-6 text-sm font-medium text-stone-700 sm:flex">
                            <Link href={route('shop.index')} className="transition hover:text-market">Shop</Link>
                            <Link href={route('about')} className="transition hover:text-market">About</Link>
                            <span className="font-semibold text-market">Contact</span>
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
                                <Link href={route('login')} className="rounded-full p-2 text-stone-700 transition hover:bg-stone-100" aria-label="Sign in">
                                    <IconUser className="h-6 w-6" />
                                </Link>
                            ) : null}
                        </div>
                    </div>
                </header>

                <main className="flex-1">
                    <section className="relative overflow-hidden border-b border-stone-200/80 bg-[#c3e9fa]">
                        <div
                            className="pointer-events-none absolute inset-0 opacity-40"
                            aria-hidden
                            style={{
                                backgroundImage:
                                    'radial-gradient(circle at 18% 75%, rgba(255,255,255,0.85) 0%, transparent 48%), radial-gradient(circle at 82% 18%, rgba(255,255,255,0.55) 0%, transparent 42%)',
                            }}
                        />
                        <div className="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
                            <Link href={route('home')} className="inline-flex items-center gap-1.5 text-sm font-medium text-[#5c4d3d]/80 transition hover:text-[#5c4d3d]">
                                <span aria-hidden>←</span> Back to home
                            </Link>
                            <p className="mt-6 text-xs font-bold uppercase tracking-[0.2em] text-[#5c4d3d]/70">Contact us</p>
                            <h1 className="mt-3 max-w-3xl text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl lg:text-5xl">We are here to help</h1>
                            <p className="mt-5 max-w-2xl text-base leading-relaxed text-stone-700 sm:text-lg">
                                Questions about orders, delivery, selling, or your account? Reach out and our team will get back to you as quickly as possible.
                            </p>
                        </div>
                    </section>

                    <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
                        <div className="grid gap-5 md:grid-cols-3">
                            <ContactCard title="Email" subtitle="General support" href={`mailto:${supportEmail}`} label={supportEmail} />
                            <ContactCard title="WhatsApp" subtitle="Quick message" href={supportWhatsAppUrl} label="Chat on WhatsApp" external />
                            <ContactCard title="Phone" subtitle="Call us" href={`tel:${supportPhone}`} label={supportPhone} />
                        </div>

                        <div className="mt-8 rounded-2xl border border-stone-200/90 bg-white p-6 shadow-sm sm:p-8">
                            <h2 className="text-xl font-bold text-stone-900">Before you reach out</h2>
                            <ul className="mt-4 list-disc space-y-2 pl-5 text-sm leading-relaxed text-stone-600">
                                <li>Include your order number for delivery or payment questions.</li>
                                <li>Use the email linked to your order/account for faster verification.</li>
                                <li>
                                    For tracking updates, you can also use the{' '}
                                    <Link href={route('orders.track')} className="font-semibold text-[#5c4d3d] underline">Track order page</Link>.
                                </li>
                            </ul>
                        </div>
                    </section>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
