import { Link, useForm, usePage } from '@inertiajs/react';
import Breadcrumbs from '@/Components/Breadcrumbs';
import LogoMark from '@/Components/LogoMark';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';
import InputError from '@/Components/InputError';

const inputClass =
    'mt-1 block w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]';

const dayOptions = [
    { value: 1, label: 'Monday' },
    { value: 2, label: 'Tuesday' },
    { value: 3, label: 'Wednesday' },
    { value: 4, label: 'Thursday' },
    { value: 5, label: 'Friday' },
    { value: 6, label: 'Saturday' },
    { value: 0, label: 'Sunday' },
];

const experienceUnits = [
    { value: 'days', label: 'Days' },
    { value: 'months', label: 'Months' },
    { value: 'years', label: 'Years' },
];

const visitModeOptions = [
    {
        value: 'Virtual',
        title: 'Virtual',
        body: 'Patients book you for video or phone appointments.',
    },
    {
        value: 'In person',
        title: 'In person',
        body: 'Patients book you for face-to-face visits.',
    },
];

function RequiredLabel({ htmlFor, children }) {
    return (
        <label htmlFor={htmlFor} className="text-sm font-medium text-stone-700">
            {children} <span className="text-red-600">*</span>
        </label>
    );
}

export default function HealthServicesEdit({ professional }) {
    const { flash } = usePage().props;
    const { data, setData, put, processing, errors } = useForm({
        name: professional.name ?? '',
        title: professional.title ?? '',
        specialty: professional.specialty ?? '',
        about: professional.about ?? '',
        location: professional.location ?? '',
        phone: professional.phone ?? '',
        email: professional.email ?? '',
        experience_amount: professional.experience_amount ?? '',
        experience_unit: professional.experience_unit ?? 'years',
        booking_note: professional.booking_note ?? '',
        visit_modes: professional.visit_modes?.length ? professional.visit_modes : ['Virtual'],
        languages: professional.languages?.length ? professional.languages : [''],
        highlights: professional.highlights?.length ? professional.highlights : [''],
        services: professional.services?.length
            ? professional.services
            : [{ name: '', visit_mode: 'Virtual', price_cedis: '', duration_minutes: 30 }],
        availability: professional.availability?.length
            ? professional.availability
            : [{ day_of_week: 1, start_time: '09:00', end_time: '17:00' }],
        is_active: Boolean(professional.is_active),
        image: null,
    });

    const toggleVisitMode = (mode) => {
        const removing = data.visit_modes.includes(mode);
        const nextModes = removing
            ? data.visit_modes.filter((m) => m !== mode)
            : [...data.visit_modes, mode];

        // Keep at least one visit type selected.
        if (nextModes.length === 0) {
            return;
        }

        const fallback = nextModes[0];
        const nextServices = data.services.map((service) => ({
            ...service,
            visit_mode: nextModes.includes(service.visit_mode) ? service.visit_mode : fallback,
        }));

        setData({
            ...data,
            visit_modes: nextModes,
            services: nextServices,
        });
    };

    const onlyOneVisitMode = data.visit_modes.length === 1;

    const updateService = (index, key, value) => {
        const next = [...data.services];
        next[index] = { ...next[index], [key]: value };
        setData('services', next);
    };

    const updateAvailability = (index, key, value) => {
        const next = [...data.availability];
        next[index] = { ...next[index], [key]: value };
        setData('availability', next);
    };

    const updateStringArray = (key, index, value) => {
        const next = [...data[key]];
        next[index] = value;
        setData(key, next);
    };

    const submit = (e) => {
        e.preventDefault();
        put(route('health-professionals.update', professional.id), {
            preserveScroll: true,
            forceFormData: Boolean(data.image),
        });
    };

    return (
        <>
            <SeoHead
                title={`Edit ${professional.name}`}
                description="Edit healthcare professional profile."
                url={route('health-professionals.edit', professional.id)}
                image={professional.image_url || '/images/logo.png'}
            />

            <div className="flex min-h-screen flex-col bg-[#faf9f7] text-stone-900 antialiased">
                <header className="border-b border-stone-200/90 bg-white/95 backdrop-blur">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
                        <LogoMark variant="shop" />
                        <Link
                            href={route('health-professionals.dashboard')}
                            className="rounded-full border border-stone-200 px-3 py-1.5 text-xs font-semibold text-stone-700 transition hover:border-market hover:text-market"
                        >
                            Back to Dashboard
                        </Link>
                    </div>
                </header>

                <div className="border-b border-stone-200/80 bg-white/95">
                    <div className="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                        <Breadcrumbs
                            tone="shop"
                            items={[
                                { label: 'Home', href: route('home') },
                                { label: 'Health Services', href: route('health-services.index') },
                                { label: 'Professional Dashboard', href: route('health-professionals.dashboard') },
                                { label: 'Edit Profile' },
                            ]}
                        />
                    </div>
                </div>

                <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
                    <h1 className="text-2xl font-bold text-stone-900 sm:text-3xl">
                        Edit Professional Profile
                    </h1>
                    <p className="mt-2 text-sm text-stone-600">
                        Update profile details, then set visit modes, services, rates, and weekly availability.
                    </p>

                    {flash?.success && (
                        <div className="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
                            {flash.success}
                        </div>
                    )}

                    <form onSubmit={submit} className="mt-6 space-y-6 rounded-2xl border border-stone-200 bg-white p-5 sm:p-7">
                        <div className="flex items-center gap-3 rounded-lg border border-stone-200 bg-stone-50 px-4 py-3">
                            <input
                                id="is_active"
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                                className="rounded border-stone-300 text-[#5c4d3d] focus:ring-[#5c4d3d]"
                            />
                            <label htmlFor="is_active" className="text-sm font-medium text-stone-700">
                                Profile is active and visible on the Health Services page
                            </label>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <RequiredLabel htmlFor="name">Full name</RequiredLabel>
                                <input id="name" className={inputClass} value={data.name} onChange={(e) => setData('name', e.target.value)} />
                                <InputError message={errors.name} className="mt-1" />
                            </div>
                            <div>
                                <RequiredLabel htmlFor="title">Professional title</RequiredLabel>
                                <input id="title" className={inputClass} value={data.title} onChange={(e) => setData('title', e.target.value)} />
                                <InputError message={errors.title} className="mt-1" />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <RequiredLabel htmlFor="specialty">Specialty</RequiredLabel>
                                <input id="specialty" className={inputClass} value={data.specialty} onChange={(e) => setData('specialty', e.target.value)} />
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
                                    />
                                    <select
                                        id="experience_unit"
                                        className={inputClass}
                                        value={data.experience_unit}
                                        onChange={(e) => setData('experience_unit', e.target.value)}
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
                            <textarea id="about" rows="4" className={inputClass} value={data.about} onChange={(e) => setData('about', e.target.value)} />
                            <InputError message={errors.about} className="mt-1" />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <RequiredLabel htmlFor="location">Location</RequiredLabel>
                                <input id="location" className={inputClass} value={data.location} onChange={(e) => setData('location', e.target.value)} />
                                <InputError message={errors.location} className="mt-1" />
                            </div>
                            <div>
                                <RequiredLabel htmlFor="phone">Phone</RequiredLabel>
                                <input id="phone" className={inputClass} value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                                <InputError message={errors.phone} className="mt-1" />
                            </div>
                        </div>

                        <div>
                            <RequiredLabel htmlFor="email">Email</RequiredLabel>
                            <input id="email" type="email" className={inputClass} value={data.email} onChange={(e) => setData('email', e.target.value)} />
                            <InputError message={errors.email} className="mt-1" />
                        </div>

                        <div>
                            <label htmlFor="image" className="text-sm font-medium text-stone-700">Replace profile image</label>
                            <input id="image" type="file" accept="image/jpeg,image/jpg,image/png,image/webp" className={inputClass} onChange={(e) => setData('image', e.target.files?.[0] ?? null)} />
                            {professional.image_url && (
                                <img src={professional.image_url} alt="" className="mt-3 h-20 w-20 rounded-xl object-cover ring-1 ring-stone-200" />
                            )}
                            <InputError message={errors.image} className="mt-1" />
                        </div>

                        <div>
                            <p className="text-sm font-medium text-stone-700">
                                How do patients visit you? <span className="text-red-600">*</span>
                            </p>
                            <p className="mt-1 text-xs text-stone-500">
                                Choose one or both. This controls which visit types appear on your public profile.
                            </p>
                            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                {visitModeOptions.map((option) => {
                                    const selected = data.visit_modes.includes(option.value);

                                    return (
                                        <button
                                            key={option.value}
                                            type="button"
                                            onClick={() => toggleVisitMode(option.value)}
                                            aria-pressed={selected}
                                            className={`rounded-xl border px-4 py-3 text-left transition ${
                                                selected
                                                    ? 'border-[#5c4d3d] bg-[#5c4d3d]/5 ring-1 ring-[#5c4d3d]'
                                                    : 'border-stone-200 bg-white hover:border-stone-300'
                                            }`}
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <p className="text-sm font-semibold text-stone-900">{option.title}</p>
                                                <span
                                                    className={`mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded border ${
                                                        selected
                                                            ? 'border-[#5c4d3d] bg-[#5c4d3d] text-white'
                                                            : 'border-stone-300 bg-white'
                                                    }`}
                                                    aria-hidden
                                                >
                                                    {selected ? (
                                                        <svg viewBox="0 0 12 12" className="h-3 w-3" fill="none">
                                                            <path
                                                                d="M2.5 6.5L4.5 8.5L9.5 3.5"
                                                                stroke="currentColor"
                                                                strokeWidth="1.8"
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                            />
                                                        </svg>
                                                    ) : null}
                                                </span>
                                            </div>
                                            <p className="mt-1 text-xs leading-relaxed text-stone-600">{option.body}</p>
                                        </button>
                                    );
                                })}
                            </div>
                            <InputError message={errors.visit_modes} className="mt-1" />
                        </div>

                        <section>
                            <div className="mb-2 flex items-center justify-between gap-3">
                                <div>
                                    <h2 className="text-sm font-bold uppercase tracking-wide text-stone-500">
                                        Services &amp; rates
                                    </h2>
                                    <p className="mt-1 text-xs text-stone-500">
                                        Each row is one bookable offering
                                        {onlyOneVisitMode
                                            ? ` (all set to ${data.visit_modes[0]}).`
                                            : '. Pick Virtual or In person for each service.'}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() =>
                                        setData('services', [
                                            ...data.services,
                                            {
                                                name: '',
                                                visit_mode: data.visit_modes[0] || 'Virtual',
                                                price_cedis: '',
                                                duration_minutes: 30,
                                            },
                                        ])
                                    }
                                    className="shrink-0 text-xs font-semibold text-market hover:underline"
                                >
                                    + Add service
                                </button>
                            </div>
                            <div className="space-y-3">
                                {data.services.map((service, index) => (
                                    <div
                                        key={`service-${index}`}
                                        className={`grid gap-3 rounded-xl border border-stone-200 bg-stone-50 p-3 ${
                                            onlyOneVisitMode ? 'sm:grid-cols-3' : 'sm:grid-cols-4'
                                        }`}
                                    >
                                        <div>
                                            <label
                                                htmlFor={`service-name-${index}`}
                                                className="text-xs font-medium text-stone-600"
                                            >
                                                Service name
                                            </label>
                                            <input
                                                id={`service-name-${index}`}
                                                className={inputClass}
                                                placeholder="e.g. Eye consultation"
                                                value={service.name}
                                                onChange={(e) => updateService(index, 'name', e.target.value)}
                                            />
                                        </div>
                                        {!onlyOneVisitMode ? (
                                            <div>
                                                <label className="text-xs font-medium text-stone-600">Visit type</label>
                                                <div className="mt-1 flex h-[42px] overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                                                    {data.visit_modes.map((mode) => {
                                                        const active = service.visit_mode === mode;

                                                        return (
                                                            <button
                                                                key={mode}
                                                                type="button"
                                                                onClick={() => updateService(index, 'visit_mode', mode)}
                                                                className={`flex flex-1 items-center justify-center px-2 text-xs font-semibold transition ${
                                                                    active
                                                                        ? 'bg-[#5c4d3d] text-white'
                                                                        : 'text-stone-600 hover:bg-stone-50'
                                                                }`}
                                                            >
                                                                {mode}
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                        ) : null}
                                        <div>
                                            <label
                                                htmlFor={`service-price-${index}`}
                                                className="text-xs font-medium text-stone-600"
                                            >
                                                Price (GHS)
                                            </label>
                                            <input
                                                id={`service-price-${index}`}
                                                type="number"
                                                min="1"
                                                className={inputClass}
                                                placeholder="e.g. 150"
                                                value={service.price_cedis}
                                                onChange={(e) => updateService(index, 'price_cedis', e.target.value)}
                                            />
                                        </div>
                                        <div>
                                            <label
                                                htmlFor={`service-duration-${index}`}
                                                className="text-xs font-medium text-stone-600"
                                            >
                                                Duration (mins)
                                            </label>
                                            <input
                                                id={`service-duration-${index}`}
                                                type="number"
                                                min="5"
                                                className={inputClass}
                                                value={service.duration_minutes}
                                                onChange={(e) =>
                                                    updateService(index, 'duration_minutes', e.target.value)
                                                }
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                            {onlyOneVisitMode ? (
                                <p className="mt-2 text-xs text-stone-500">
                                    Visit type for every service is{' '}
                                    <span className="font-semibold text-stone-700">{data.visit_modes[0]}</span>. Select
                                    both options above if some services are Virtual and others are In person.
                                </p>
                            ) : null}
                            <InputError message={errors.services} className="mt-1" />
                        </section>

                        <section>
                            <div className="mb-2 flex items-center justify-between gap-3">
                                <div>
                                    <h2 className="text-sm font-bold uppercase tracking-wide text-stone-500">
                                        Weekly availability
                                    </h2>
                                    <p className="mt-1 text-xs text-stone-500">
                                        Days and hours you&apos;re open for bookings.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() =>
                                        setData('availability', [
                                            ...data.availability,
                                            { day_of_week: 1, start_time: '09:00', end_time: '17:00' },
                                        ])
                                    }
                                    className="shrink-0 text-xs font-semibold text-market hover:underline"
                                >
                                    + Add window
                                </button>
                            </div>
                            <div className="space-y-3">
                                {data.availability.map((slot, index) => (
                                    <div
                                        key={`availability-${index}`}
                                        className="grid gap-3 rounded-xl border border-stone-200 bg-stone-50 p-3 sm:grid-cols-3"
                                    >
                                        <div>
                                            <label
                                                htmlFor={`availability-day-${index}`}
                                                className="text-xs font-medium text-stone-600"
                                            >
                                                Day
                                            </label>
                                            <select
                                                id={`availability-day-${index}`}
                                                className={inputClass}
                                                value={slot.day_of_week}
                                                onChange={(e) =>
                                                    updateAvailability(index, 'day_of_week', Number(e.target.value))
                                                }
                                            >
                                                {dayOptions.map((day) => (
                                                    <option key={day.value} value={day.value}>
                                                        {day.label}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                htmlFor={`availability-start-${index}`}
                                                className="text-xs font-medium text-stone-600"
                                            >
                                                Start time
                                            </label>
                                            <input
                                                id={`availability-start-${index}`}
                                                type="time"
                                                className={inputClass}
                                                value={slot.start_time}
                                                onChange={(e) =>
                                                    updateAvailability(index, 'start_time', e.target.value)
                                                }
                                            />
                                        </div>
                                        <div>
                                            <label
                                                htmlFor={`availability-end-${index}`}
                                                className="text-xs font-medium text-stone-600"
                                            >
                                                End time
                                            </label>
                                            <input
                                                id={`availability-end-${index}`}
                                                type="time"
                                                className={inputClass}
                                                value={slot.end_time}
                                                onChange={(e) =>
                                                    updateAvailability(index, 'end_time', e.target.value)
                                                }
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <InputError message={errors.availability} className="mt-1" />
                        </section>

                        <section className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="text-sm font-medium text-stone-700">Languages (optional)</label>
                                {data.languages.map((lang, index) => (
                                    <input key={`lang-${index}`} className={inputClass} value={lang} onChange={(e) => updateStringArray('languages', index, e.target.value)} />
                                ))}
                                <button type="button" onClick={() => setData('languages', [...data.languages, ''])} className="mt-2 text-xs font-semibold text-market hover:underline">+ Add language</button>
                            </div>
                            <div>
                                <label className="text-sm font-medium text-stone-700">Care highlights (optional)</label>
                                {data.highlights.map((h, index) => (
                                    <input key={`highlight-${index}`} className={inputClass} value={h} onChange={(e) => updateStringArray('highlights', index, e.target.value)} />
                                ))}
                                <button type="button" onClick={() => setData('highlights', [...data.highlights, ''])} className="mt-2 text-xs font-semibold text-market hover:underline">+ Add highlight</button>
                            </div>
                        </section>

                        <div>
                            <label htmlFor="booking_note" className="text-sm font-medium text-stone-700">Booking note</label>
                            <textarea id="booking_note" rows="2" className={inputClass} value={data.booking_note} onChange={(e) => setData('booking_note', e.target.value)} />
                        </div>

                        <button disabled={processing} className="w-full rounded-xl bg-[#5c4d3d] py-3 text-sm font-semibold text-white transition hover:bg-[#4a3e32] disabled:opacity-50">
                            {processing ? 'Saving…' : 'Save changes'}
                        </button>
                    </form>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
