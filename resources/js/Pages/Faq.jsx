import { useState } from 'react';
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

function FaqItem({ question, children, defaultOpen = false }) {
    const [open, setOpen] = useState(defaultOpen);

    return (
        <div className="border-b border-stone-200/90 last:border-b-0">
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="flex w-full items-start justify-between gap-4 py-5 text-left"
                aria-expanded={open}
            >
                <span className="text-base font-semibold text-stone-900">{question}</span>
                <span
                    className={`mt-0.5 shrink-0 text-xl font-light leading-none text-stone-400 transition ${open ? 'rotate-45' : ''}`}
                    aria-hidden
                >
                    +
                </span>
            </button>
            {open ? <div className="pb-5 text-sm leading-relaxed text-stone-600">{children}</div> : null}
        </div>
    );
}

function FaqList({ items }) {
    return (
        <div className="rounded-2xl border border-stone-200/90 bg-white px-5 shadow-sm sm:px-6">
            {items.map((item) => (
                <FaqItem key={item.question} question={item.question}>
                    {item.answer}
                </FaqItem>
            ))}
        </div>
    );
}

const categories = [
    {
        id: 'general',
        label: 'General',
        items: [
            {
                question: 'What is Mummish?',
                answer: (
                    <p>
                        Mummish is Ghana&apos;s trusted online marketplace for mothers and families. We make it easy to
                        buy and sell new, fairly new, and pre-loved baby and maternity items, connect with trusted
                        service providers, and access helpful parenting resources.
                    </p>
                ),
            },
            {
                question: 'How do I create an account?',
                answer: (
                    <ol className="list-decimal space-y-2 pl-5">
                        <li>Click Sign Up.</li>
                        <li>Enter your details.</li>
                        <li>Verify your email address or phone number.</li>
                        <li>Complete your profile.</li>
                        <li>You&apos;re ready to start buying or selling.</li>
                    </ol>
                ),
            },
        ],
    },
    {
        id: 'vendors',
        label: 'For vendors (sellers)',
        items: [
            {
                question: 'Who can sell on Mummish?',
                answer: (
                    <p>
                        Anyone in Ghana with quality baby or maternity items can sell on Mummish. Whether you&apos;re a
                        parent decluttering, a baby store, or a maternity business, you&apos;re welcome to join our
                        platform.
                    </p>
                ),
            },
            {
                question: 'What can I list?',
                answer: (
                    <div className="space-y-4">
                        <p>You can list items in three categories:</p>
                        <ul className="space-y-2">
                            <li>
                                <span className="font-semibold text-stone-800">New:</span> Unused items, preferably with
                                original packaging or tags.
                            </li>
                            <li>
                                <span className="font-semibold text-stone-800">Fairly New:</span> Gently used items
                                that are clean, well-maintained, and in excellent condition.
                            </li>
                            <li>
                                <span className="font-semibold text-stone-800">Used:</span> Items that have been used
                                but are still safe, clean, and functional.
                            </li>
                        </ul>
                        <p>
                            Examples include, but are not limited to: baby clothes, shoes, strollers, cots/cribs, toys,
                            nursery furniture, and books.
                        </p>
                    </div>
                ),
            },
            {
                question: 'How do I list an item?',
                answer: (
                    <ol className="list-decimal space-y-2 pl-5">
                        <li>Log into your account.</li>
                        <li>Click &ldquo;List an Item&rdquo;.</li>
                        <li>Select the appropriate category.</li>
                        <li>Upload clear photos.</li>
                        <li>Add a title, description, and price.</li>
                        <li>Select the item&apos;s condition (New, Fairly New, Used).</li>
                        <li>Submit your listing for review.</li>
                    </ol>
                ),
            },
            {
                question: 'What kind of photos should I upload?',
                answer: (
                    <p>
                        Use clear, well-lit photos taken from different angles. Show the item&apos;s actual condition and
                        highlight any defects honestly. Clean items and good photos build buyer confidence.
                    </p>
                ),
            },
            {
                question: 'How and when do I receive payment?',
                answer: (
                    <div className="space-y-3">
                        <p>Payments can be received through:</p>
                        <ul className="list-disc space-y-1 pl-5">
                            <li>Mobile Money</li>
                            <li>Bank Transfer</li>
                        </ul>
                        <p>Withdrawals are processed within 1–3 business days.</p>
                    </div>
                ),
            },
            {
                question: 'Is there a fee to sell on Mummish?',
                answer: (
                    <p>Mummish charges a 10% service fee on the final sale price.</p>
                ),
            },
        ],
    },
    {
        id: 'buyers',
        label: 'For buyers',
        items: [
            {
                question: 'What can I find on Mummish?',
                answer: (
                    <div className="space-y-4">
                        <p>Mummish offers everything parents need in one place, including:</p>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p className="font-semibold text-stone-800">Baby essentials</p>
                                <ul className="mt-2 list-disc space-y-1 pl-5">
                                    <li>Clothing</li>
                                    <li>Shoes</li>
                                    <li>Strollers</li>
                                    <li>Cots/Cribs</li>
                                    <li>Walkers</li>
                                    <li>Toys and books</li>
                                    <li>Feeding accessories</li>
                                    <li>Nursery furniture</li>
                                </ul>
                            </div>
                            <div>
                                <p className="font-semibold text-stone-800">Maternity essentials</p>
                                <ul className="mt-2 list-disc space-y-1 pl-5">
                                    <li>Maternity wear</li>
                                    <li>Nursing accessories</li>
                                    <li>Breast pumps</li>
                                </ul>
                            </div>
                            <div>
                                <p className="font-semibold text-stone-800">Services</p>
                                <ul className="mt-2 list-disc space-y-1 pl-5">
                                    <li>Nannies</li>
                                    <li>Midwives</li>
                                    <li>Pediatricians</li>
                                    <li>Childcare providers</li>
                                    <li>Yoga instructors</li>
                                    <li>Gym instructors</li>
                                    <li>Lactation consultants</li>
                                    <li>Parenting experts</li>
                                    <li>Baby photographers</li>
                                </ul>
                            </div>
                            <div>
                                <p className="font-semibold text-stone-800">Community &amp; resources</p>
                                <ul className="mt-2 list-disc space-y-1 pl-5">
                                    <li>Parenting tips</li>
                                    <li>Educational articles</li>
                                    <li>Helpful resources</li>
                                    <li>Support for parents</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                ),
            },
            {
                question: 'Are the sellers verified?',
                answer: (
                    <p>
                        Mummish reviews listings and works to ensure products meet our quality standards before they
                        are made available to buyers.
                    </p>
                ),
            },
            {
                question: 'Will I receive exactly what I ordered?',
                answer: (
                    <p>
                        Yes. Sellers are required to provide accurate descriptions and clear photos so buyers know
                        exactly what to expect. If an item you receive is significantly different from what was
                        advertised, please report it to us within 24 hours of delivery and we will investigate and
                        resolve the issue.
                    </p>
                ),
            },
            {
                question: 'How long will delivery take?',
                answer: (
                    <p>
                        Most orders are delivered within 1–7 business days, depending on your location in Ghana. See our{' '}
                        <Link href="/shipping" className="font-semibold text-[#5c4d3d] underline">
                            shipping page
                        </Link>{' '}
                        for more detail.
                    </p>
                ),
            },
            {
                question: 'What payment methods do you accept?',
                answer: (
                    <div className="space-y-3">
                        <p>We accept:</p>
                        <ul className="list-disc space-y-1 pl-5">
                            <li>Mobile Money</li>
                            <li>Bank Transfers</li>
                        </ul>
                    </div>
                ),
            },
        ],
    },
];

export default function Faq() {
    const { auth, canLogin, canRegister, supportEmail, supportPhone, supportWhatsAppUrl } = usePage().props;
    const { openCart, count: cartCount } = useCart();
    const [activeCategory, setActiveCategory] = useState('general');
    const activeItems = categories.find((category) => category.id === activeCategory)?.items ?? [];

    return (
        <>
            <SeoHead
                title="FAQ"
                description="Answers to common questions about buying and selling on Mummish — Ghana's marketplace for mothers, families, and baby essentials."
                url="/faq"
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
                            <span className="font-semibold text-market">FAQ</span>
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
                            <Link
                                href={route('home')}
                                className="inline-flex items-center gap-1.5 text-sm font-medium text-[#5c4d3d]/80 transition hover:text-[#5c4d3d]"
                            >
                                <span aria-hidden>←</span> Back to home
                            </Link>
                            <p className="mt-6 text-xs font-bold uppercase tracking-[0.2em] text-[#5c4d3d]/70">
                                Help centre
                            </p>
                            <h1 className="mt-3 max-w-3xl text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl lg:text-5xl">
                                Frequently asked questions
                            </h1>
                            <p className="mt-5 max-w-2xl text-base leading-relaxed text-stone-700 sm:text-lg">
                                Find answers about buying, selling, payments, and delivery on Mummish.
                            </p>
                        </div>
                    </section>

                    <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
                        <div className="flex flex-wrap gap-2">
                            {categories.map((category) => (
                                <button
                                    key={category.id}
                                    type="button"
                                    onClick={() => setActiveCategory(category.id)}
                                    className={`rounded-full px-4 py-2 text-sm font-semibold transition ${
                                        activeCategory === category.id
                                            ? 'bg-[#5c4d3d] text-white shadow-sm'
                                            : 'border border-stone-200 bg-white text-stone-700 hover:border-stone-300 hover:bg-stone-50'
                                    }`}
                                >
                                    {category.label}
                                </button>
                            ))}
                        </div>

                        <div className="mt-8">
                            <h2 className="text-xl font-bold text-stone-900 sm:text-2xl">
                                {categories.find((category) => category.id === activeCategory)?.label}
                            </h2>
                            <div className="mt-5">
                                <FaqList items={activeItems} />
                            </div>
                        </div>
                    </section>

                    <section className="border-t border-stone-200/80 bg-white">
                        <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-16 lg:px-8">
                            <div className="overflow-hidden rounded-3xl bg-[#5c4d3d] text-white shadow-lg">
                                <div className="grid lg:grid-cols-5">
                                    <div className="p-8 sm:p-10 lg:col-span-2 lg:p-12">
                                        <p className="text-xs font-bold uppercase tracking-[0.2em] text-white/60">
                                            Need more help?
                                        </p>
                                        <h2 className="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">
                                            Our support team is always happy to help
                                        </h2>
                                    </div>
                                    <div className="flex flex-col justify-center gap-4 border-t border-white/10 p-8 sm:p-10 lg:col-span-3 lg:border-l lg:border-t-0 lg:p-12">
                                        <div className="grid gap-3 sm:grid-cols-3">
                                            <a
                                                href={`mailto:${supportEmail}`}
                                                className="rounded-xl border border-white/20 bg-white/5 px-4 py-3 text-center text-sm font-semibold transition hover:bg-white/10"
                                            >
                                                Email
                                            </a>
                                            <a
                                                href={supportWhatsAppUrl}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="rounded-xl border border-white/20 bg-white/5 px-4 py-3 text-center text-sm font-semibold transition hover:bg-white/10"
                                            >
                                                WhatsApp
                                            </a>
                                            <a
                                                href={`tel:${supportPhone}`}
                                                className="rounded-xl border border-white/20 bg-white/5 px-4 py-3 text-center text-sm font-semibold transition hover:bg-white/10"
                                            >
                                                Phone
                                            </a>
                                        </div>
                                        <p className="text-sm leading-relaxed text-white/85">
                                            Reach us at{' '}
                                            <a href={`mailto:${supportEmail}`} className="font-semibold underline">
                                                {supportEmail}
                                            </a>{' '}
                                            and we&apos;ll point you to the right channel.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
