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

function Subheading({ children }) {
    return <h3 className="text-base font-bold text-stone-900">{children}</h3>;
}

const toc = [
    { id: 'data-collected', label: 'Data collected' },
    { id: 'purpose', label: 'Purpose of collection' },
    { id: 'sharing', label: 'Data sharing' },
    { id: 'rights', label: 'Your rights under Act 843' },
    { id: 'candidate', label: 'Candidate privacy policy' },
    { id: 'cookies', label: 'Cookie policy' },
    { id: 'copyright', label: 'Copyright infringement policy' },
];

export default function Privacy() {
    const { auth, canLogin, canRegister, supportEmail } = usePage().props;
    const { openCart, count: cartCount } = useCart();

    return (
        <>
            <SeoHead
                title="Privacy Policy"
                description="How Mummish collects, uses, and protects your family's personal information under the Ghana Data Protection Act, 2012 (Act 843)."
                url={route('privacy')}
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
                                Privacy Policy
                            </h1>
                            <p className="mt-3 text-sm text-stone-500">Last updated: July 2026</p>
                            <p className="mt-6 text-base leading-relaxed text-stone-600">
                                Mummish is deeply committed to protecting your family&apos;s personal information. This
                                Privacy Policy outlines our data practices under the Ghana Data Protection Act, 2012
                                (Act 843).
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
                            <Section id="data-collected" number="1" title="Data Collected">
                                <p>We collect information necessary to maintain a secure marketplace:</p>
                                <ul className="list-disc space-y-2 pl-5">
                                    <li>
                                        <span className="font-semibold text-stone-800">Account Data:</span> Name, phone
                                        number, email address, physical/delivery address.
                                    </li>
                                    <li>
                                        <span className="font-semibold text-stone-800">Transaction Data:</span> Mobile
                                        Money numbers (for payouts), transaction history, and listed items.
                                    </li>
                                </ul>
                            </Section>

                            <Section id="purpose" number="2" title="Purpose of Collection">
                                <p>We use your data strictly to:</p>
                                <ul className="list-disc space-y-2 pl-5">
                                    <li>Verify accounts and prevent fraudulent activities.</li>
                                    <li>Facilitate deliveries between buyers and sellers.</li>
                                    <li>Process payouts and handle customer support requests.</li>
                                </ul>
                            </Section>

                            <Section id="sharing" number="3" title="Data Sharing">
                                <p>
                                    We do not sell your personal data. We share your information only with trusted third
                                    parties critical to your transactions:
                                </p>
                                <ul className="list-disc space-y-2 pl-5">
                                    <li>Logistics and delivery partners (to drop off your purchases).</li>
                                    <li>Licensed Ghanaian payment processors.</li>
                                    <li>
                                        Law enforcement authorities if strictly requested under a valid legal order.
                                    </li>
                                </ul>
                            </Section>

                            <Section id="rights" number="4" title="Your Rights under Act 843">
                                <p>
                                    You have the right to access your data, request corrections to inaccurate profiles,
                                    or ask for the deletion of your account information at any time by contacting our
                                    Data Protection Officer at{' '}
                                    <a
                                        href={`mailto:${supportEmail}`}
                                        className="font-semibold text-[#5c4d3d] underline-offset-2 hover:underline"
                                    >
                                        {supportEmail}
                                    </a>
                                    .
                                </p>
                            </Section>

                            <Section id="candidate" number="5" title="Candidate Privacy Policy">
                                <p>
                                    If you are applying to join our professional team as an employee or intern to help
                                    build the future for our little bosses, this policy details how we treat your data
                                    during recruitment, pursuant to Act 843.
                                </p>

                                <div className="space-y-3">
                                    <Subheading>1. Information Collected</Subheading>
                                    <p>We collect the data you provide via your application:</p>
                                    <ul className="list-disc space-y-2 pl-5">
                                        <li>Full name, contact details, CV, cover letter, and employment history.</li>
                                        <li>
                                            References and background checks (where relevant for roles dealing with
                                            community trust and children&apos;s safety).
                                        </li>
                                    </ul>
                                </div>

                                <div className="space-y-3">
                                    <Subheading>2. How We Use It</Subheading>
                                    <p>
                                        This information is used strictly to evaluate your suitability for employment,
                                        schedule interviews, and verify references.
                                    </p>
                                </div>

                                <div className="space-y-3">
                                    <Subheading>3. Data Retention</Subheading>
                                    <ul className="list-disc space-y-2 pl-5">
                                        <li>
                                            If you are hired, this information becomes part of your employee record.
                                        </li>
                                        <li>
                                            If your application is unsuccessful, we retain your data for up to one year
                                            to match you with future openings, after which it is securely deleted,
                                            unless you request removal sooner.
                                        </li>
                                    </ul>
                                </div>
                            </Section>

                            <Section id="cookies" number="6" title="Cookie Policy">
                                <div className="space-y-3">
                                    <Subheading>1. What Are Cookies?</Subheading>
                                    <p>
                                        Cookies are small text files stored on your phone, tablet, or computer when you
                                        browse the Mummish app or website. They help us remember your preferences and
                                        make your marketplace experience smoother.
                                    </p>
                                </div>

                                <div className="space-y-3">
                                    <Subheading>2. How We Use Cookies</Subheading>
                                    <ul className="list-disc space-y-2 pl-5">
                                        <li>
                                            <span className="font-semibold text-stone-800">Essential Cookies:</span>{' '}
                                            These are vital for keeping you logged into your parent account and
                                            remembering items in your cart.
                                        </li>
                                        <li>
                                            <span className="font-semibold text-stone-800">Performance Cookies:</span>{' '}
                                            These analyze how parents navigate the platform, letting us know if a page
                                            is loading slowly or breaking.
                                        </li>
                                        <li>
                                            <span className="font-semibold text-stone-800">Preference Cookies:</span>{' '}
                                            These store your regional preferences, like showing you listings closest to
                                            Accra, Kumasi, or Takoradi first.
                                        </li>
                                    </ul>
                                </div>

                                <div className="space-y-3">
                                    <Subheading>3. Managing Choices</Subheading>
                                    <p>
                                        You can easily adjust your device settings or browser preferences to block or
                                        delete cookies. However, please note that turning off essential cookies might
                                        prevent certain marketplace functionalities from working correctly.
                                    </p>
                                </div>
                            </Section>

                            <Section id="copyright" number="7" title="Copyright Infringement Policy">
                                <p>
                                    Mummish respects Intellectual Property (IP) rights. We aim to host a trusted,
                                    authentic marketplace for pre-loved child goods.
                                </p>

                                <div className="space-y-3">
                                    <Subheading>1. Stock Photos vs. Authentic Photos</Subheading>
                                    <p>
                                        Because our platform values transparency, accuracy in our condition tiers (New,
                                        Fairly New, Used), and waste-reduction, we heavily encourage parents to upload
                                        authentic, original photos of their actual items rather than corporate stock
                                        photos.
                                    </p>
                                </div>

                                <div className="space-y-3">
                                    <Subheading>2. Reporting Infringement</Subheading>
                                    <p>
                                        If you believe that any listing or imagery on Mummish infringes upon your
                                        copyright or trademark under Ghanaian IP laws, please submit a formal Takedown
                                        Notice to our team containing:
                                    </p>
                                    <ul className="list-disc space-y-2 pl-5">
                                        <li>Your physical or electronic signature.</li>
                                        <li>Identification of the copyrighted work claimed to have been infringed.</li>
                                        <li>
                                            The exact link or item ID of the infringing product on our platform.
                                        </li>
                                        <li>Your contact details (phone number and email).</li>
                                    </ul>
                                    <p>
                                        Send all notices to{' '}
                                        <a
                                            href={`mailto:${supportEmail}`}
                                            className="font-semibold text-[#5c4d3d] underline-offset-2 hover:underline"
                                        >
                                            {supportEmail}
                                        </a>
                                        . Upon validation, we will promptly remove the offending material and warn or
                                        suspend the non-compliant vendor account.
                                    </p>
                                </div>
                            </Section>
                        </div>

                        <p className="mt-12 text-sm text-stone-500">
                            Related:{' '}
                            <Link href={route('terms')} className="font-medium text-[#5c4d3d] hover:underline">
                                Terms and Conditions
                            </Link>
                            {' · '}
                            <Link href={route('billing')} className="font-medium text-[#5c4d3d] hover:underline">
                                Billing Policy
                            </Link>
                        </p>
                    </div>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
