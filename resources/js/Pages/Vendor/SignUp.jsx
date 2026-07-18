import { useEffect, useRef, useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import Breadcrumbs from '@/Components/Breadcrumbs';
import LogoMark from '@/Components/LogoMark';
import SiteFooter from '@/Components/SiteFooter';
import InputError from '@/Components/InputError';

const vendorBrown = 'bg-[#5c4d3d] hover:bg-[#4a3e32]';
const vendorBrownOutline =
    'border-2 border-[#5c4d3d] bg-white text-[#5c4d3d] hover:bg-[#5c4d3d]/5';

function IconStore(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72l1.189-1.19A2.25 2.25 0 015.378 3h13.243a2.25 2.25 0 011.591.659l1.19 1.189a3 3 0 01-.621 4.72M6.75 21h3.75a.75.75 0 00.75-.75V12.75a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v7.5c0 .414.336.75.75.75z"
            />
        </svg>
    );
}

function IconShield(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
            />
        </svg>
    );
}

function IconUsers(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
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

const whyPartner = [
    {
        title: 'Easy Store Setup',
        body: 'Launch your shop in minutes with guided listing tools built for busy parents and small businesses.',
        icon: IconStore,
        iconBg: 'bg-orange-100 text-orange-700',
    },
    {
        title: 'Secure Payments',
        body: 'Get paid reliably with protected checkout and clear payout timelines you can trust.',
        icon: IconShield,
        iconBg: 'bg-emerald-100 text-emerald-700',
    },
    {
        title: 'Reach More Parents',
        body: 'Connect with families actively shopping for quality kids’ and baby gear across Ghana.',
        icon: IconUsers,
        iconBg: 'bg-sky-100 text-sky-700',
    },
];

const journeySteps = [
    {
        step: 1,
        title: 'List your products',
        body: 'Upload photos, set your price, and publish listings from your phone in under a minute.',
        color: 'bg-orange-500',
    },
    {
        step: 2,
        title: 'Sell to parents',
        body: 'Answer questions, fulfill orders, and build repeat customers in our family-first marketplace.',
        color: 'bg-emerald-500',
    },
    {
        step: 3,
        title: 'Get paid',
        body: 'Receive payouts securely once orders are completed—simple, transparent, and on your schedule.',
        color: 'bg-sky-500',
    },
];

const communityPoints = [
    'Dedicated vendor support team',
    'Marketing features for top sellers',
    'Low commission for the first 3 months',
];

const inputClass =
    'mt-1 block w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]';

function RequiredLabel({ htmlFor, children }) {
    return (
        <label htmlFor={htmlFor} className="text-sm font-medium text-stone-700">
            {children}
            <span className="text-red-600" aria-hidden>
                {' '}
                *
            </span>
        </label>
    );
}

function splitName(name) {
    if (!name) {
        return { first: '', last: '' };
    }
    const parts = name.trim().split(/\s+/);
    const first = parts.shift() ?? '';
    return { first, last: parts.join(' ') };
}

function isFormComplete(data, isAuthenticated) {
    const vendorFieldsComplete =
        data.first_name.trim() !== '' &&
        data.last_name.trim() !== '' &&
        data.shop_name.trim() !== '' &&
        data.phone.trim() !== '' &&
        data.ghana_card_id.trim() !== '' &&
        data.category !== '' &&
        data.terms_accepted === true;

    if (!vendorFieldsComplete) {
        return false;
    }

    if (isAuthenticated) {
        return true;
    }

    return (
        data.email.trim() !== '' &&
        data.password.length >= 8 &&
        data.password === data.password_confirmation
    );
}

export default function VendorSignUp({ categories, existingApplication, referral_code: initialReferralCode = '' }) {
    const { auth, canLogin } = usePage().props;
    const isAuthenticated = Boolean(auth?.user);
    const nameParts = splitName(auth?.user?.name);
    const formRef = useRef(null);
    const logoInputRef = useRef(null);
    const [logoPreview, setLogoPreview] = useState(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        first_name: nameParts.first,
        last_name: nameParts.last,
        email: auth?.user?.email ?? '',
        password: '',
        password_confirmation: '',
        shop_name: '',
        phone: '',
        ghana_card_id: '',
        category: '',
        referral_code: initialReferralCode ?? '',
        terms_accepted: false,
        logo: null,
    });

    useEffect(() => {
        return () => {
            if (logoPreview) {
                URL.revokeObjectURL(logoPreview);
            }
        };
    }, [logoPreview]);

    const handleLogoChange = (e) => {
        const file = e.target.files?.[0];

        if (!file) {
            return;
        }

        setData('logo', file);

        if (logoPreview) {
            URL.revokeObjectURL(logoPreview);
        }

        setLogoPreview(URL.createObjectURL(file));
    };

    const clearLogo = () => {
        setData('logo', null);

        if (logoPreview) {
            URL.revokeObjectURL(logoPreview);
        }

        setLogoPreview(null);

        if (logoInputRef.current) {
            logoInputRef.current.value = '';
        }
    };

    const submit = (e) => {
        e.preventDefault();
        if (!isFormComplete(data, isAuthenticated)) {
            return;
        }
        post(route('vendor.signup.store'), {
            preserveScroll: true,
            forceFormData: Boolean(data.logo),
            onSuccess: () => {
                reset('password', 'password_confirmation', 'logo');
                clearLogo();
            },
        });
    };

    const canSubmit = isFormComplete(data, isAuthenticated) && !processing;

    return (
        <>
            <Head title="Sell on Mummish — Vendor sign up" />

            <div className="flex min-h-screen flex-col bg-[#faf9f7] text-stone-900 antialiased">
                <header className="border-b border-stone-200/90 bg-white/95 backdrop-blur">
                    <div className="mx-auto flex max-w-7xl items-center gap-4 px-4 py-4 sm:px-6 lg:px-8">
                        <LogoMark />
                        <nav className="hidden flex-1 items-center justify-center gap-8 md:flex">
                            <Link href={route('shop.index')} className="text-sm font-medium text-stone-600 hover:text-stone-900">
                                Search
                            </Link>
                            <a href="#journey" className="text-sm font-medium text-stone-600 hover:text-stone-900">
                                How it Works
                            </a>
                            <span className="text-sm font-semibold text-[#5c4d3d]">Sell</span>
                        </nav>
                        <div className="ml-auto flex items-center gap-2">
                            {canLogin &&
                                (auth.user ? (
                                    <Link
                                        href={route('dashboard')}
                                        className="rounded-full bg-orange-100 px-4 py-2 text-sm font-semibold text-[#5c4d3d] transition hover:bg-orange-200"
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <Link
                                        href={route('login')}
                                        className="rounded-full bg-orange-100 px-4 py-2 text-sm font-semibold text-[#5c4d3d] transition hover:bg-orange-200"
                                    >
                                        Log In
                                    </Link>
                                ))}
                        </div>
                    </div>
                </header>

                <div className="border-b border-stone-200/90 bg-white/90">
                    <div className="mx-auto max-w-7xl px-4 py-2.5 sm:px-6 lg:px-8">
                        <Breadcrumbs
                            tone="shop"
                            items={[
                                { label: 'Home', href: '/' },
                                { label: 'Sell' },
                            ]}
                        />
                    </div>
                </div>

                <main className="flex-1">
                    {/* Hero */}
                    <section className="border-b border-stone-200/60 bg-[#faf9f7]">
                        <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-16 lg:py-16 lg:px-8">
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl lg:text-[2.5rem] lg:leading-tight">
                                    Grow your business with Mummish
                                </h1>
                                <p className="mt-4 max-w-lg text-base leading-relaxed text-stone-600 sm:text-lg">
                                    Join Ghana&apos;s trusted marketplace for pre-loved and new kids&apos; essentials. Reach
                                    thousands of parents looking for quality, sustainability, and value.
                                </p>
                                <div className="mt-8 flex flex-wrap gap-3">
                                    <a
                                        href="#apply"
                                        className={`inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-semibold text-white shadow-sm transition ${vendorBrown}`}
                                    >
                                        Start Selling
                                    </a>
                                    <a
                                        href="#why"
                                        className={`inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-semibold transition ${vendorBrownOutline}`}
                                    >
                                        Learn More
                                    </a>
                                </div>
                            </div>
                            <div className="relative">
                                <div className="overflow-hidden rounded-2xl bg-gradient-to-br from-orange-100 via-amber-50 to-teal-100 shadow-lg ring-1 ring-stone-200/80">
                                    <img
                                        src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=800&h=600&q=80"
                                        alt=""
                                        className="aspect-[4/3] w-full object-cover object-center"
                                    />
                                </div>
                                <div className="absolute bottom-4 left-4 rounded-xl bg-white/95 px-4 py-3 shadow-md ring-1 ring-stone-200/80 backdrop-blur-sm">
                                    <p className="text-lg font-bold text-stone-900">4.9/5</p>
                                    <p className="text-xs font-medium text-stone-600">Vendor Happiness Score</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Why partner */}
                    <section id="why" className="scroll-mt-24 bg-white py-14 sm:py-16">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="mx-auto max-w-2xl text-center">
                                <h2 className="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">Why partner with us?</h2>
                                <p className="mt-3 text-stone-600">
                                    Everything you need to launch, sell, and scale without the overhead of running your own
                                    storefront.
                                </p>
                            </div>
                            <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {whyPartner.map((card) => (
                                    <article
                                        key={card.title}
                                        className="rounded-2xl border border-stone-100 bg-white p-6 shadow-sm"
                                    >
                                        <div
                                            className={`flex h-11 w-11 items-center justify-center rounded-xl ${card.iconBg}`}
                                        >
                                            <card.icon className="h-6 w-6" aria-hidden />
                                        </div>
                                        <h3 className="mt-4 text-lg font-bold text-stone-900">{card.title}</h3>
                                        <p className="mt-2 text-sm leading-relaxed text-stone-600">{card.body}</p>
                                    </article>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* Journey */}
                    <section id="journey" className="scroll-mt-24 border-y border-stone-200/60 bg-[#faf9f7] py-14 sm:py-16">
                        <div className="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                            <div>
                                <h2 className="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">
                                    Starting your journey is simple
                                </h2>
                                <p className="mt-3 text-stone-600">
                                    From your first listing to your first payout, we keep the process clear and
                                    parent-friendly.
                                </p>
                            </div>
                            <ol className="relative space-y-8 border-l-2 border-stone-200 pl-8">
                                {journeySteps.map((item) => (
                                    <li key={item.step} className="relative">
                                        <span
                                            className={`absolute -left-[2.35rem] flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold text-white ${item.color}`}
                                        >
                                            {item.step}
                                        </span>
                                        <h3 className="font-bold text-stone-900">{item.title}</h3>
                                        <p className="mt-1 text-sm leading-relaxed text-stone-600">{item.body}</p>
                                    </li>
                                ))}
                            </ol>
                        </div>
                    </section>

                    {/* Application */}
                    <section id="apply" ref={formRef} className="scroll-mt-24 bg-stone-100/80 py-14 sm:py-16">
                        <div className="mx-auto grid max-w-7xl gap-10 px-4 lg:grid-cols-2 lg:gap-12 sm:px-6 lg:px-8">
                            <div className="rounded-2xl border border-stone-200/80 bg-white p-6 shadow-sm sm:p-8">
                                <h2 className="text-xl font-bold text-stone-900 sm:text-2xl">
                                    Vendor application &amp; account
                                </h2>
                                <p className="mt-2 text-sm text-stone-600">
                                    {isAuthenticated
                                        ? 'Complete your shop details below. You are already logged in.'
                                        : 'Create your vendor login and shop profile in one step. After submitting, sign in anytime with your email and password.'}
                                </p>

                                {existingApplication ? (
                                    <div
                                        className="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950"
                                        role="status"
                                    >
                                        You already submitted a vendor application
                                        {existingApplication.shop_name
                                            ? ` for “${existingApplication.shop_name}”`
                                            : ''}
                                        . Status: <span className="font-semibold">{existingApplication.status}</span>.
                                        <Link href={route('dashboard')} className="ml-1 font-semibold text-[#5c4d3d] underline">
                                            Go to dashboard
                                        </Link>
                                    </div>
                                ) : (
                                <form onSubmit={submit} className="mt-6 space-y-6" noValidate>
                                    <p className="text-xs text-stone-500">
                                        Fields marked with <span className="text-red-600">*</span> are required.
                                    </p>

                                    {!isAuthenticated && (
                                        <fieldset className="space-y-4 rounded-xl border border-stone-200 bg-stone-50/80 p-4">
                                            <legend className="px-1 text-sm font-bold text-stone-900">
                                                Your login details
                                            </legend>
                                            <div>
                                                <RequiredLabel htmlFor="email">Email address</RequiredLabel>
                                                <input
                                                    id="email"
                                                    type="email"
                                                    value={data.email}
                                                    onChange={(e) => setData('email', e.target.value)}
                                                    className={inputClass}
                                                    autoComplete="username"
                                                    required
                                                />
                                                <p className="mt-1 text-xs text-stone-500">
                                                    You&apos;ll use this email to log in after applying.
                                                </p>
                                                <InputError message={errors.email} className="mt-1" />
                                            </div>
                                            <div>
                                                <RequiredLabel htmlFor="password">Password</RequiredLabel>
                                                <input
                                                    id="password"
                                                    type="password"
                                                    value={data.password}
                                                    onChange={(e) => setData('password', e.target.value)}
                                                    className={inputClass}
                                                    autoComplete="new-password"
                                                    required
                                                    minLength={8}
                                                />
                                                <InputError message={errors.password} className="mt-1" />
                                            </div>
                                            <div>
                                                <RequiredLabel htmlFor="password_confirmation">
                                                    Confirm password
                                                </RequiredLabel>
                                                <input
                                                    id="password_confirmation"
                                                    type="password"
                                                    value={data.password_confirmation}
                                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                                    className={inputClass}
                                                    autoComplete="new-password"
                                                    required
                                                    minLength={8}
                                                />
                                                <InputError message={errors.password_confirmation} className="mt-1" />
                                            </div>
                                        </fieldset>
                                    )}

                                    {isAuthenticated && (
                                        <div className="rounded-lg border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                                            Applying as <span className="font-semibold">{auth.user.email}</span>
                                        </div>
                                    )}

                                    <fieldset className="space-y-4">
                                        <legend className="text-sm font-bold text-stone-900">Shop details</legend>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <RequiredLabel htmlFor="first_name">First Name</RequiredLabel>
                                            <input
                                                id="first_name"
                                                type="text"
                                                value={data.first_name}
                                                onChange={(e) => setData('first_name', e.target.value)}
                                                className={inputClass}
                                                autoComplete="given-name"
                                                required
                                            />
                                            <InputError message={errors.first_name} className="mt-1" />
                                        </div>
                                        <div>
                                            <RequiredLabel htmlFor="last_name">Last Name</RequiredLabel>
                                            <input
                                                id="last_name"
                                                type="text"
                                                value={data.last_name}
                                                onChange={(e) => setData('last_name', e.target.value)}
                                                className={inputClass}
                                                autoComplete="family-name"
                                                required
                                            />
                                            <InputError message={errors.last_name} className="mt-1" />
                                        </div>
                                    </div>

                                    <div>
                                        <RequiredLabel htmlFor="shop_name">Shop Name</RequiredLabel>
                                        <input
                                            id="shop_name"
                                            type="text"
                                            value={data.shop_name}
                                            onChange={(e) => setData('shop_name', e.target.value)}
                                            className={inputClass}
                                            required
                                        />
                                        <InputError message={errors.shop_name} className="mt-1" />
                                    </div>

                                    <div>
                                        <label htmlFor="shop_logo" className="text-sm font-medium text-stone-700">
                                            Shop logo <span className="text-stone-400">(optional)</span>
                                        </label>
                                        <div className="mt-2 flex items-center gap-4">
                                            <div className="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-stone-100 ring-1 ring-stone-200">
                                                {logoPreview ? (
                                                    <img
                                                        src={logoPreview}
                                                        alt=""
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    <span className="text-lg font-bold text-stone-400">
                                                        {(data.shop_name.trim()[0] || '?').toUpperCase()}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <input
                                                    ref={logoInputRef}
                                                    id="shop_logo"
                                                    type="file"
                                                    accept="image/jpeg,image/jpg,image/png,image/webp"
                                                    onChange={handleLogoChange}
                                                    className="sr-only"
                                                />
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => logoInputRef.current?.click()}
                                                        className="inline-flex cursor-pointer items-center rounded-lg bg-[#5c4d3d]/10 px-3 py-2 text-sm font-semibold text-[#5c4d3d] transition hover:bg-[#5c4d3d]/20 hover:text-[#4a3e32] active:scale-[0.98]"
                                                    >
                                                        Choose file
                                                    </button>
                                                    {data.logo ? (
                                                        <span className="truncate text-sm text-stone-600">
                                                            {data.logo.name}
                                                        </span>
                                                    ) : (
                                                        <span className="text-sm text-stone-400">No file chosen</span>
                                                    )}
                                                </div>
                                                {logoPreview ? (
                                                    <button
                                                        type="button"
                                                        onClick={clearLogo}
                                                        className="mt-2 text-xs font-medium text-stone-500 underline hover:text-stone-700"
                                                    >
                                                        Remove logo
                                                    </button>
                                                ) : null}
                                            </div>
                                        </div>
                                        <p className="mt-1 text-xs text-stone-500">
                                            Shown on your shop page and in the store circles on the homepage. JPG or PNG up to 2 MB.
                                        </p>
                                        <InputError message={errors.logo} className="mt-1" />
                                    </div>

                                    <div>
                                        <RequiredLabel htmlFor="phone">Phone Number</RequiredLabel>
                                        <input
                                            id="phone"
                                            type="tel"
                                            value={data.phone}
                                            onChange={(e) => setData('phone', e.target.value)}
                                            className={inputClass}
                                            autoComplete="tel"
                                            placeholder="e.g. 024 123 4567"
                                            required
                                        />
                                        <InputError message={errors.phone} className="mt-1" />
                                    </div>

                                    <div>
                                        <RequiredLabel htmlFor="ghana_card_id">Ghana Card ID number</RequiredLabel>
                                        <input
                                            id="ghana_card_id"
                                            type="text"
                                            value={data.ghana_card_id}
                                            onChange={(e) => setData('ghana_card_id', e.target.value.toUpperCase())}
                                            className={inputClass}
                                            autoComplete="off"
                                            placeholder="GHA-123456789-0"
                                            inputMode="text"
                                            required
                                        />
                                        <p className="mt-1 text-xs text-stone-500">
                                            Enter the ID number printed on your Ghana Card (format GHA-123456789-0).
                                        </p>
                                        <InputError message={errors.ghana_card_id} className="mt-1" />
                                    </div>

                                    <div>
                                        <RequiredLabel htmlFor="category">What do you sell?</RequiredLabel>
                                        <select
                                            id="category"
                                            value={data.category}
                                            onChange={(e) => setData('category', e.target.value)}
                                            className={inputClass}
                                            required
                                        >
                                            <option value="">Select a category</option>
                                            {Object.entries(categories).map(([value, label]) => (
                                                <option key={value} value={value}>
                                                    {label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.category} className="mt-1" />
                                    </div>

                                    <div>
                                        <label htmlFor="referral_code" className="text-sm font-medium text-stone-700">
                                            Referral code <span className="text-stone-400">(optional)</span>
                                        </label>
                                        <input
                                            id="referral_code"
                                            type="text"
                                            value={data.referral_code}
                                            onChange={(e) => setData('referral_code', e.target.value.toUpperCase())}
                                            className={inputClass}
                                            autoComplete="off"
                                            placeholder="e.g. PARTNER2026"
                                        />
                                        <p className="mt-1 text-xs text-stone-500">
                                            Have a referral code? Enter it to credit the person who invited you.
                                        </p>
                                        <InputError message={errors.referral_code} className="mt-1" />
                                    </div>
                                    </fieldset>

                                    <div className="flex items-start gap-2 pt-1">
                                        <input
                                            id="terms_accepted"
                                            name="terms_accepted"
                                            type="checkbox"
                                            checked={data.terms_accepted}
                                            onChange={(e) => setData('terms_accepted', e.target.checked)}
                                            required
                                            className="mt-1 rounded border-stone-300 text-[#5c4d3d] focus:ring-[#5c4d3d]"
                                        />
                                        <label htmlFor="terms_accepted" className="text-sm text-stone-600">
                                            I agree to the{' '}
                                            <Link
                                                href={route('terms')}
                                                className="font-medium text-[#5c4d3d] underline hover:text-market"
                                                target="_blank"
                                            >
                                                Marketplace Terms &amp; Conditions
                                            </Link>{' '}
                                            and Vendor Agreement.
                                            <span className="text-red-600" aria-hidden>
                                                {' '}
                                                *
                                            </span>
                                        </label>
                                    </div>
                                    <InputError message={errors.terms_accepted} className="mt-1" />

                                    <button
                                        type="submit"
                                        disabled={!canSubmit}
                                        className={`mt-2 w-full rounded-xl py-3.5 text-sm font-semibold text-white shadow-sm transition disabled:cursor-not-allowed disabled:opacity-40 ${vendorBrown}`}
                                    >
                                        {processing
                                            ? 'Submitting…'
                                            : isAuthenticated
                                              ? 'Submit application'
                                              : 'Create account & apply'}
                                    </button>
                                </form>
                                )}
                            </div>

                            <aside className="space-y-6">
                                <blockquote className="rounded-2xl bg-orange-50/90 p-6 ring-1 ring-orange-100 sm:p-8">
                                    <p className="font-serif text-lg leading-relaxed text-stone-800 sm:text-xl">
                                        &ldquo;Switching to Mummish doubled our monthly revenue in just two months.
                                        The platform is so intuitive for small business owners like me.&rdquo;
                                    </p>
                                    <footer className="mt-6 flex items-center gap-3">
                                        <img
                                            src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=96&h=96&q=80"
                                            alt=""
                                            className="h-12 w-12 rounded-full object-cover ring-2 ring-white"
                                        />
                                        <div>
                                            <p className="font-semibold text-stone-900">Sarah Jenkins</p>
                                            <p className="text-sm text-stone-600">Founder, Little Knot</p>
                                        </div>
                                    </footer>
                                </blockquote>

                                <div className="rounded-2xl border border-stone-200/80 bg-white p-6 shadow-sm sm:p-8">
                                    <h3 className="text-lg font-bold text-stone-900">
                                        Join a community of 500+ small businesses
                                    </h3>
                                    <ul className="mt-4 space-y-3">
                                        {communityPoints.map((point) => (
                                            <li key={point} className="flex items-start gap-2 text-sm text-stone-700">
                                                <IconCheck className="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" aria-hidden />
                                                {point}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </aside>
                        </div>
                    </section>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
