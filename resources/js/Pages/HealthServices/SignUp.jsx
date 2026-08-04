import { useEffect, useRef, useState } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import Breadcrumbs from '@/Components/Breadcrumbs';
import LogoMark from '@/Components/LogoMark';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';
import InputError from '@/Components/InputError';

const brandBrown = 'bg-[#5c4d3d] hover:bg-[#4a3e32]';
const brandBrownOutline =
    'border-2 border-[#5c4d3d] bg-white text-[#5c4d3d] hover:bg-[#5c4d3d]/5';

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

function IconRates(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"
            />
        </svg>
    );
}

function IconCalendar(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
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

const whyJoin = [
    {
        title: 'Reach more patients',
        body: 'Appear alongside trusted providers so patients can find care by specialty, visit mode, and availability.',
        icon: IconUsers,
        iconBg: 'bg-sky-100 text-sky-700',
    },
    {
        title: 'Set your own rates',
        body: 'Publish clear service packages for virtual and in-person visits. You control pricing and duration.',
        icon: IconRates,
        iconBg: 'bg-emerald-100 text-emerald-700',
    },
    {
        title: 'Manage your calendar',
        body: 'Define weekly availability once, then update slots anytime from your professional dashboard.',
        icon: IconCalendar,
        iconBg: 'bg-orange-100 text-orange-700',
    },
];

const journeySteps = [
    {
        step: 1,
        title: 'Create your account',
        body: 'Register with your email and password and set up your professional profile.',
        color: 'bg-sky-500',
    },
    {
        step: 2,
        title: 'Set rates & availability',
        body: 'After login, add visit modes, services, and weekly hours from your dashboard.',
        color: 'bg-emerald-500',
    },
    {
        step: 3,
        title: 'Receive bookings',
        body: 'Activate your profile and start getting booking requests from patients on Mummish.',
        color: 'bg-orange-500',
    },
];

const communityPoints = [
    'Secure login to manage your practice',
    'Add services and rates anytime',
    'Set weekly availability from your dashboard',
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

function isFormComplete(data) {
    return (
        data.name.trim() !== '' &&
        data.title.trim() !== '' &&
        data.specialty.trim() !== '' &&
        data.about.trim() !== '' &&
        data.location.trim() !== '' &&
        data.phone.trim() !== '' &&
        data.email.trim() !== '' &&
        data.password.length >= 8 &&
        data.password === data.password_confirmation &&
        Number(data.experience_amount) >= 1 &&
        ['days', 'months', 'years'].includes(data.experience_unit) &&
        Boolean(data.image) &&
        data.terms_accepted === true
    );
}

const experienceUnits = [
    { value: 'days', label: 'Days' },
    { value: 'months', label: 'Months' },
    { value: 'years', label: 'Years' },
];

export default function HealthServicesSignUp() {
    const { auth, canLogin, homeUrl } = usePage().props;
    const isAuthenticated = Boolean(auth?.user);
    const formRef = useRef(null);
    const imageInputRef = useRef(null);
    const [imagePreview, setImagePreview] = useState(null);

    const { data, setData, post, processing, errors } = useForm({
        name: auth?.user?.name ?? '',
        title: '',
        specialty: '',
        about: '',
        location: '',
        phone: auth?.user?.phone ?? '',
        email: auth?.user?.email ?? '',
        password: '',
        password_confirmation: '',
        experience_amount: '',
        experience_unit: 'years',
        image: null,
        terms_accepted: false,
    });

    useEffect(() => {
        return () => {
            if (imagePreview) {
                URL.revokeObjectURL(imagePreview);
            }
        };
    }, [imagePreview]);

    const handleImageChange = (e) => {
        const file = e.target.files?.[0];

        if (!file) {
            return;
        }

        setData('image', file);

        if (imagePreview) {
            URL.revokeObjectURL(imagePreview);
        }

        setImagePreview(URL.createObjectURL(file));
    };

    const clearImage = () => {
        setData('image', null);

        if (imagePreview) {
            URL.revokeObjectURL(imagePreview);
        }

        setImagePreview(null);

        if (imageInputRef.current) {
            imageInputRef.current.value = '';
        }
    };

    const submit = (e) => {
        e.preventDefault();
        if (!isFormComplete(data)) {
            return;
        }
        post(route('health-professionals.signup.store'), {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    const canSubmit = isFormComplete(data) && !processing;

    return (
        <>
            <SeoHead
                title="Join as a Healthcare Professional"
                description="Create your Mummish healthcare account, publish your profile and rates, and start receiving family bookings."
                url={route('health-professionals.signup')}
                image="/images/logo.png"
            />

            <div className="flex min-h-screen flex-col bg-[#faf9f7] text-stone-900 antialiased">
                <header className="border-b border-stone-200/90 bg-white/95 backdrop-blur">
                    <div className="mx-auto flex max-w-7xl items-center gap-4 px-4 py-4 sm:px-6 lg:px-8">
                        <LogoMark variant="shop" />
                        <nav className="hidden flex-1 items-center justify-center gap-8 md:flex">
                            <Link
                                href={route('health-services.index')}
                                className="text-sm font-medium text-stone-600 hover:text-stone-900"
                            >
                                Health Services
                            </Link>
                            <a href="#journey" className="text-sm font-medium text-stone-600 hover:text-stone-900">
                                How it Works
                            </a>
                            <span className="text-sm font-semibold text-[#5c4d3d]">Join</span>
                        </nav>
                        <div className="ml-auto flex items-center gap-2">
                            {canLogin &&
                                (auth.user ? (
                                    <Link
                                        href={homeUrl || route('dashboard')}
                                        className="rounded-full bg-sky-100 px-4 py-2 text-sm font-semibold text-[#5c4d3d] transition hover:bg-sky-200"
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <Link
                                        href={route('login')}
                                        className="rounded-full bg-sky-100 px-4 py-2 text-sm font-semibold text-[#5c4d3d] transition hover:bg-sky-200"
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
                                { label: 'Home', href: route('home') },
                                { label: 'Health Services', href: route('health-services.index') },
                                { label: 'Join' },
                            ]}
                        />
                    </div>
                </div>

                <main className="flex-1">
                    <section className="border-b border-stone-200/60 bg-[#faf9f7]">
                        <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-16 lg:px-8 lg:py-16">
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl lg:text-[2.5rem] lg:leading-tight">
                                    Join as a Healthcare Professional on Mummish
                                </h1>
                                <p className="mt-4 max-w-lg text-base leading-relaxed text-stone-600 sm:text-lg">
                                    Create your login, publish your specialty and rates, and help families book virtual
                                    or in-person care with confidence.
                                </p>
                                <div className="mt-8 flex flex-wrap gap-3">
                                    <a
                                        href="#apply"
                                        className={`inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-semibold text-white shadow-sm transition ${brandBrown}`}
                                    >
                                        Sign Up
                                    </a>
                                    <a
                                        href="#why"
                                        className={`inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-semibold transition ${brandBrownOutline}`}
                                    >
                                        Learn more
                                    </a>
                                </div>
                            </div>
                            <div className="relative">
                                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-sky-100 via-teal-50 to-emerald-100 shadow-lg ring-1 ring-stone-200/80">
                                    <img
                                        src="/images/health/professional-hero.png"
                                        alt="Healthcare professional using a phone"
                                        className="aspect-[4/3] w-full object-cover object-center"
                                    />
                                    <div className="absolute bottom-4 left-4 right-4 sm:right-auto">
                                        <div className="inline-block max-w-[14rem] rounded-xl bg-white/95 px-4 py-3 shadow-md ring-1 ring-stone-200/80 backdrop-blur-sm">
                                            <p className="text-lg font-bold text-stone-900">Families first</p>
                                            <p className="text-xs font-medium text-stone-600">
                                                Care that fits elite Patients
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="why" className="scroll-mt-24 bg-white py-14 sm:py-16">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="mx-auto max-w-2xl text-center">
                                <h2 className="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">
                                    Why join Mummish Health Services?
                                </h2>
                                <p className="mt-3 text-stone-600">
                                    Everything you need to present your practice clearly and stay in control of how
                                    families book with you.
                                </p>
                            </div>
                            <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {whyJoin.map((card) => (
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

                    <section id="journey" className="scroll-mt-24 border-y border-stone-200/60 bg-[#faf9f7] py-14 sm:py-16">
                        <div className="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                            <div>
                                <h2 className="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">
                                    Starting is simple
                                </h2>
                                <p className="mt-3 text-stone-600">
                                    From account creation to your first booking request, we keep the path clear for
                                    busy clinicians.
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

                    <section id="apply" ref={formRef} className="scroll-mt-24 bg-stone-100/80 py-14 sm:py-16">
                        <div className="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-12 lg:px-8">
                            <div className="rounded-2xl border border-stone-200/80 bg-white p-6 shadow-sm sm:p-8">
                                <h2 className="text-xl font-bold text-stone-900 sm:text-2xl">
                                    Healthcare Professional registration
                                </h2>
                                <p className="mt-2 text-sm text-stone-600">
                                    Create your login and profile. You&apos;ll add services and availability after
                                    signing in. Already registered?{' '}
                                    <Link href={route('login')} className="font-semibold text-[#5c4d3d] underline">
                                        Sign in
                                    </Link>
                                </p>

                                <form onSubmit={submit} className="mt-6 space-y-6" noValidate>
                                    <p className="text-xs text-stone-500">
                                        Fields marked with <span className="text-red-600">*</span> are required.
                                    </p>

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
                                                You&apos;ll use this email to log in after registering.
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

                                    <fieldset className="space-y-4">
                                        <legend className="text-sm font-bold text-stone-900">
                                            Professional profile
                                        </legend>

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <RequiredLabel htmlFor="name">Full name</RequiredLabel>
                                                <input
                                                    id="name"
                                                    className={inputClass}
                                                    value={data.name}
                                                    onChange={(e) => setData('name', e.target.value)}
                                                    autoComplete="name"
                                                    required
                                                />
                                                <InputError message={errors.name} className="mt-1" />
                                            </div>
                                            <div>
                                                <RequiredLabel htmlFor="title">Professional title</RequiredLabel>
                                                <input
                                                    id="title"
                                                    className={inputClass}
                                                    placeholder="e.g. Pediatrician"
                                                    value={data.title}
                                                    onChange={(e) => setData('title', e.target.value)}
                                                    required
                                                />
                                                <InputError message={errors.title} className="mt-1" />
                                            </div>
                                        </div>

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <RequiredLabel htmlFor="specialty">Specialty</RequiredLabel>
                                                <input
                                                    id="specialty"
                                                    className={inputClass}
                                                    value={data.specialty}
                                                    onChange={(e) => setData('specialty', e.target.value)}
                                                    required
                                                />
                                                <InputError message={errors.specialty} className="mt-1" />
                                            </div>
                                            <div>
                                                <RequiredLabel htmlFor="experience_amount">Experience</RequiredLabel>
                                                <div className="mt-1 grid grid-cols-2 gap-2">
                                                    <input
                                                        id="experience_amount"
                                                        type="number"
                                                        min="1"
                                                        max="100"
                                                        inputMode="numeric"
                                                        className={inputClass}
                                                        placeholder="e.g. 8"
                                                        value={data.experience_amount}
                                                        onChange={(e) => setData('experience_amount', e.target.value)}
                                                        required
                                                    />
                                                    <select
                                                        id="experience_unit"
                                                        className={inputClass}
                                                        value={data.experience_unit}
                                                        onChange={(e) => setData('experience_unit', e.target.value)}
                                                        required
                                                    >
                                                        {experienceUnits.map((unit) => (
                                                            <option key={unit.value} value={unit.value}>
                                                                {unit.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                                <InputError message={errors.experience_amount || errors.experience_unit} className="mt-1" />
                                            </div>
                                        </div>

                                        <div>
                                            <RequiredLabel htmlFor="about">About</RequiredLabel>
                                            <textarea
                                                id="about"
                                                rows="4"
                                                className={inputClass}
                                                value={data.about}
                                                onChange={(e) => setData('about', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.about} className="mt-1" />
                                        </div>

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <RequiredLabel htmlFor="location">Location</RequiredLabel>
                                                <input
                                                    id="location"
                                                    className={inputClass}
                                                    value={data.location}
                                                    onChange={(e) => setData('location', e.target.value)}
                                                    required
                                                />
                                                <InputError message={errors.location} className="mt-1" />
                                            </div>
                                            <div>
                                                <RequiredLabel htmlFor="phone">Phone</RequiredLabel>
                                                <input
                                                    id="phone"
                                                    type="tel"
                                                    className={inputClass}
                                                    value={data.phone}
                                                    onChange={(e) => setData('phone', e.target.value)}
                                                    autoComplete="tel"
                                                    required
                                                />
                                                <InputError message={errors.phone} className="mt-1" />
                                            </div>
                                        </div>

                                        <div>
                                            <RequiredLabel htmlFor="profile_image">
                                                Professional Profile Image
                                            </RequiredLabel>
                                            <div className="mt-2 flex items-center gap-4">
                                                <div className="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-stone-100 ring-1 ring-stone-200">
                                                    {imagePreview ? (
                                                        <img
                                                            src={imagePreview}
                                                            alt=""
                                                            className="h-full w-full object-cover"
                                                        />
                                                    ) : (
                                                        <span className="text-lg font-bold text-stone-400">
                                                            {(data.name.trim()[0] || '?').toUpperCase()}
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <input
                                                        ref={imageInputRef}
                                                        id="profile_image"
                                                        type="file"
                                                        accept="image/jpeg,image/jpg,image/png,image/webp"
                                                        onChange={handleImageChange}
                                                        className="sr-only"
                                                        required
                                                    />
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => imageInputRef.current?.click()}
                                                            className="inline-flex cursor-pointer items-center rounded-lg bg-[#5c4d3d]/10 px-3 py-2 text-sm font-semibold text-[#5c4d3d] transition hover:bg-[#5c4d3d]/20 hover:text-[#4a3e32] active:scale-[0.98]"
                                                        >
                                                            Choose file
                                                        </button>
                                                        {data.image ? (
                                                            <span className="truncate text-sm text-stone-600">
                                                                {data.image.name}
                                                            </span>
                                                        ) : (
                                                            <span className="text-sm text-stone-400">No file chosen</span>
                                                        )}
                                                    </div>
                                                    {imagePreview ? (
                                                        <button
                                                            type="button"
                                                            onClick={clearImage}
                                                            className="mt-2 text-xs font-medium text-stone-500 underline hover:text-stone-700"
                                                        >
                                                            Remove image
                                                        </button>
                                                    ) : null}
                                                </div>
                                            </div>
                                            <p className="mt-1 text-xs text-stone-500">
                                                Shown on your public profile. JPG or PNG up to 2 MB.
                                            </p>
                                            <InputError message={errors.image} className="mt-1" />
                                        </div>
                                    </fieldset>

                                    <p className="rounded-lg border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-sky-950">
                                        After you create your account, you&apos;ll set visit modes, services &amp; rates,
                                        weekly availability, and optional details from your dashboard.
                                    </p>

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
                                            and Healthcare Professional Agreement.
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
                                        className={`mt-2 w-full rounded-xl py-3.5 text-sm font-semibold text-white shadow-sm transition disabled:cursor-not-allowed disabled:opacity-40 ${brandBrown}`}
                                    >
                                        {processing ? 'Creating account…' : 'Create account & register'}
                                    </button>
                                </form>
                            </div>

                            <aside className="space-y-6">
                                <blockquote className="rounded-2xl bg-sky-50/90 p-6 ring-1 ring-sky-100 sm:p-8">
                                    <p className="font-serif text-lg leading-relaxed text-stone-800 sm:text-xl">
                                        &ldquo;Parents need clear options for care. Publishing my rates and weekly
                                        hours on Mummish made it easier for families to book with confidence.&rdquo;
                                    </p>
                                    <footer className="mt-6">
                                        <p className="font-semibold text-stone-900">Dr. Addae-Poku</p>
                                        <p className="text-sm text-stone-600">Ophthalmologist</p>
                                    </footer>
                                </blockquote>

                                <div className="rounded-2xl border border-stone-200/80 bg-white p-6 shadow-sm sm:p-8">
                                    <h3 className="text-lg font-bold text-stone-900">What you get</h3>
                                    <ul className="mt-4 space-y-3">
                                        {communityPoints.map((point) => (
                                            <li key={point} className="flex items-start gap-2 text-sm text-stone-700">
                                                <IconCheck
                                                    className="mt-0.5 h-5 w-5 shrink-0 text-emerald-600"
                                                    aria-hidden
                                                />
                                                {point}
                                            </li>
                                        ))}
                                    </ul>
                                    {isAuthenticated ? (
                                        <p className="mt-5 text-xs text-stone-500">
                                            You&apos;re signed in as {auth.user.email}. Registration creates a healthcare
                                            professional account with this form&apos;s email and password.
                                        </p>
                                    ) : null}
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
