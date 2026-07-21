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

function IconSparkle(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"
            />
        </svg>
    );
}

function IconRecycle(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3"
            />
        </svg>
    );
}

function IconHeart(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
            />
        </svg>
    );
}

const conditionTypes = [
    {
        title: 'New',
        description:
            'Unopened gifts or items your little boss never got to wear and still in original packaging or with tags.',
        accent: 'bg-emerald-50 text-emerald-800 ring-emerald-200/80',
        dot: 'bg-emerald-500',
    },
    {
        title: 'Fairly New',
        description:
            'Gently used a handful of times but still in immaculate condition, ready for another family to love.',
        accent: 'bg-sky-50 text-sky-800 ring-sky-200/80',
        dot: 'bg-sky-500',
    },
    {
        title: 'Used',
        description:
            'Well-loved gear that still has plenty of life left in it, honest listings, fair prices, less waste.',
        accent: 'bg-amber-50 text-amber-900 ring-amber-200/80',
        dot: 'bg-amber-500',
    },
];

const valueProps = [
    {
        icon: IconRecycle,
        title: 'Declutter with purpose',
        description: 'Turn outgrown clothes, toys, and gear into space and cash, instead of clutter.',
    },
    {
        icon: IconHeart,
        title: 'Built for Ghanaian families',
        description: 'A local peer-to-peer marketplace that connects sellers with buyers who get it.',
    },
    {
        icon: IconSparkle,
        title: 'Fresh starts for every item',
        description: 'What your child has outgrown can be exactly what another family is searching for.',
    },
];

export default function About() {
    const { auth, canLogin, canRegister } = usePage().props;
    const { openCart, count: cartCount } = useCart();

    return (
        <>
            <SeoHead
                title="About us"
                description="Mummish is Ghana's marketplace for families — declutter, reduce waste, and find great pre-loved gear for your little bosses."
                url={route('about')}
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
                            <span className="font-semibold text-market">About</span>
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
                    {/* Hero */}
                    <section className="relative overflow-hidden border-b border-stone-200/80 bg-[#c3e9fa]">
                        <div
                            className="pointer-events-none absolute inset-0 opacity-40"
                            aria-hidden
                            style={{
                                backgroundImage:
                                    'radial-gradient(circle at 20% 80%, rgba(255,255,255,0.8) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(255,255,255,0.6) 0%, transparent 45%)',
                            }}
                        />
                        <div className="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
                            <p className="text-xs font-bold uppercase tracking-[0.2em] text-[#5c4d3d]/70">About us</p>
                            <h1 className="mt-3 max-w-3xl text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl lg:text-5xl">
                                The marketplace for our{' '}
                                <span className="text-[#5c4d3d]">&ldquo;little bosses&rdquo;</span>
                            </h1>
                            <p className="mt-5 max-w-2xl text-base leading-relaxed text-stone-700 sm:text-lg">
                                Welcome to Mummish! Dedicated entirely to our children. Ghana&apos;s premier
                                peer-to-peer marketplace for smart parents who want to declutter, reduce waste, and
                                make money all at once.
                            </p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <Link
                                    href={route('shop.index')}
                                    className="inline-flex items-center justify-center rounded-full bg-[#5c4d3d] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#4a3e32]"
                                >
                                    Browse the shop
                                </Link>
                                {canRegister && (
                                    <Link
                                        href={route('vendor.signup')}
                                        className="inline-flex items-center justify-center rounded-full border-2 border-[#5c4d3d] bg-white/80 px-6 py-3 text-sm font-semibold text-[#5c4d3d] transition hover:bg-white"
                                    >
                                        Start selling
                                    </Link>
                                )}
                            </div>
                        </div>
                    </section>

                    {/* Story */}
                    <section className="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
                        <div className="grid gap-10 lg:grid-cols-2 lg:items-center lg:gap-16">
                            <div>
                                <h2 className="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">
                                    We get it, kids grow fast
                                </h2>
                                <div className="mt-6 space-y-4 text-base leading-relaxed text-stone-600">
                                    <p>
                                        As parents, we know how quickly kids grow. One minute they are outgrowing their
                                        onesies, and the next, their bedrooms are overflowing with toys, strollers, and
                                        gear they barely touched. It leaves our homes cluttered and our pockets lighter.
                                    </p>
                                    <p>
                                        That is where Mummish steps in. By creating a seamless bridge between local
                                        sellers and buyers, we turn items into fresh opportunities for another family.
                                    </p>
                                </div>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                                {valueProps.map(({ icon: Icon, title, description }) => (
                                    <div
                                        key={title}
                                        className="rounded-2xl border border-stone-200/90 bg-white p-5 shadow-sm"
                                    >
                                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#c3e9fa]/60 text-[#5c4d3d]">
                                            <Icon className="h-5 w-5" />
                                        </div>
                                        <h3 className="mt-4 text-sm font-bold text-stone-900">{title}</h3>
                                        <p className="mt-2 text-sm leading-relaxed text-stone-600">{description}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* Condition types */}
                    <section className="border-y border-stone-200/80 bg-white">
                        <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
                            <div className="max-w-2xl">
                                <h2 className="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">
                                    Something for every stage
                                </h2>
                                <p className="mt-4 text-base leading-relaxed text-stone-600">
                                    Whether you have items that are completely new, fairly new, or well-loved used gear
                                    that still has plenty of life left in it, Mummish makes it safe and easy to find
                                    them a new home.
                                </p>
                            </div>
                            <div className="mt-10 grid gap-5 sm:grid-cols-3">
                                {conditionTypes.map(({ title, description, accent, dot }) => (
                                    <article
                                        key={title}
                                        className={`rounded-2xl p-6 ring-1 ${accent}`}
                                    >
                                        <div className="flex items-center gap-2">
                                            <span className={`h-2.5 w-2.5 rounded-full ${dot}`} aria-hidden />
                                            <h3 className="text-lg font-bold">{title}</h3>
                                        </div>
                                        <p className="mt-3 text-sm leading-relaxed opacity-90">{description}</p>
                                    </article>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* Mission */}
                    <section className="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
                        <div className="overflow-hidden rounded-3xl bg-[#5c4d3d] text-white shadow-lg">
                            <div className="grid lg:grid-cols-5">
                                <div className="p-8 sm:p-10 lg:col-span-2 lg:p-12">
                                    <p className="text-xs font-bold uppercase tracking-[0.2em] text-white/60">
                                        Our mission
                                    </p>
                                    <h2 className="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">
                                        A circular economy for Ghanaian families
                                    </h2>
                                </div>
                                <div className="flex items-center border-t border-white/10 p-8 sm:p-10 lg:col-span-3 lg:border-l lg:border-t-0 lg:p-12">
                                    <p className="text-base leading-relaxed text-white/90 sm:text-lg">
                                        To foster a sustainable, circular economy for Ghanaian families. We help parents
                                        clear out the old, fund the new, and protect our environment by reducing waste
                                        , one little step at a time.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* CTA */}
                    <section className="border-t border-stone-200/80 bg-white">
                        <div className="mx-auto max-w-7xl px-4 py-14 text-center sm:px-6 sm:py-16 lg:px-8">
                            <h2 className="text-xl font-bold text-stone-900 sm:text-2xl">
                                Ready to declutter or discover your next find?
                            </h2>
                            <p className="mx-auto mt-3 max-w-lg text-sm text-stone-600 sm:text-base">
                                Join families across Ghana buying and selling thoughtfully on Mummish.
                            </p>
                            <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                                <Link
                                    href={route('shop.index')}
                                    className="inline-flex items-center justify-center rounded-full bg-market px-8 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-market-hover"
                                >
                                    Shop now
                                </Link>
                                {canRegister && (
                                    <Link
                                        href={route('vendor.signup')}
                                        className="inline-flex items-center justify-center rounded-full border-2 border-market px-8 py-3 text-sm font-semibold text-market transition hover:bg-market-muted"
                                    >
                                        Sell on Mummish
                                    </Link>
                                )}
                            </div>
                        </div>
                    </section>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
