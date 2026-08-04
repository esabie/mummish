import { Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import Breadcrumbs from '@/Components/Breadcrumbs';
import LogoMark from '@/Components/LogoMark';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';

function StarRow({ rating }) {
    const full = Math.round(Number(rating) || 0);
    return (
        <div className="flex items-center gap-0.5" aria-hidden>
            {[1, 2, 3, 4, 5].map((i) => (
                <svg
                    key={i}
                    className={`h-4 w-4 ${i <= full ? 'text-amber-400' : 'text-stone-200'}`}
                    viewBox="0 0 24 24"
                    fill="currentColor"
                >
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                </svg>
            ))}
        </div>
    );
}

export default function HealthServicesShow({ professional }) {
    const visitModes = professional.visit_modes ?? [];
    const [visitType, setVisitType] = useState(visitModes[0] ?? null);

    // Filter out slots in the past. Evaluated fresh each render so stale open tabs also get correct results.
    const availableSlots = useMemo(() => {
        const now = new Date();
        return (professional.slots ?? []).map((day) => {
            const isToday = day.date.toLowerCase() === 'today';
            const filteredTimes = isToday
                ? day.times.filter((t) => {
                      const [time, meridiem] = t.split(' ');
                      let [hours, minutes] = time.split(':').map(Number);
                      if (meridiem === 'PM' && hours !== 12) hours += 12;
                      if (meridiem === 'AM' && hours === 12) hours = 0;
                      const slotTime = new Date(now);
                      slotTime.setHours(hours, minutes, 0, 0);
                      return slotTime > now;
                  })
                : day.times;
            return { ...day, times: filteredTimes };
        }).filter((day) => day.times.length > 0);
    }, [professional.slots]);
    const [selectedService, setSelectedService] = useState(null);
    const [selectedDate, setSelectedDate] = useState(null);
    const [selectedTime, setSelectedTime] = useState(null);
    const [showAllTimes, setShowAllTimes] = useState(false);

    const filteredServices = (professional.rate_card ?? []).filter(
        (item) => !item.mode || !visitType || item.mode === visitType
    );

    const activeDay = professional.slots?.find((d) => d.date === selectedDate) ?? null;
    const visibleTimes = showAllTimes ? activeDay?.times : activeDay?.times?.slice(0, 4);

    return (
        <>
            <SeoHead
                title={`${professional.name} | Health Services`}
                description={professional.about}
                url={route('health-services.show', professional.slug)}
                image={professional.image}
            />

            <div className="flex min-h-screen flex-col bg-[#f7f5f2] text-stone-900 antialiased">
                <header className="border-b border-stone-200/90 bg-white/95 backdrop-blur">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
                        <Link
                            href={route('health-services.index')}
                            className="shrink-0 text-sm font-semibold text-[#5c4d3d] hover:text-market hover:underline"
                        >
                            ← Back to Health Services
                        </Link>
                        <LogoMark variant="shop" className="min-w-0 flex-1 justify-center" />
                        <Link
                            href={route('shop.index')}
                            className="shrink-0 rounded-full border border-stone-200 px-3 py-1.5 text-xs font-semibold text-stone-700 transition hover:border-market hover:text-market"
                        >
                            Shop
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
                                { label: professional.name },
                            ]}
                        />
                    </div>
                </div>

                <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                    <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.85fr)] lg:gap-12">

                        {/* ── Left column ── */}
                        <div className="space-y-6">

                            {/* Mobile: compact side-by-side layout */}
                            <div className="flex gap-4 sm:hidden">
                                <div className="h-28 w-28 shrink-0 overflow-hidden rounded-2xl bg-stone-100">
                                    <img
                                        src={professional.image}
                                        alt={professional.name}
                                        className="h-full w-full object-cover object-top"
                                    />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <h1 className="text-xl font-bold tracking-tight text-[#3d3429]">
                                        {professional.name}
                                    </h1>
                                    <p className="mt-0.5 text-sm font-medium text-stone-600">{professional.title}</p>
                                    {professional.review_count > 0 && (
                                        <div className="mt-1.5 flex flex-wrap items-center gap-1.5">
                                            <StarRow rating={professional.rating} />
                                            <span className="text-xs font-semibold text-stone-700">
                                                {Number(professional.rating).toFixed(1)}
                                            </span>
                                            <span className="text-xs text-stone-500">
                                                ({professional.review_count})
                                            </span>
                                        </div>
                                    )}
                                    <div className="mt-2 flex flex-wrap gap-1.5">
                                        <span className="rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-semibold text-sky-700 ring-1 ring-sky-100">
                                            {professional.specialty}
                                        </span>
                                        {visitModes.map((mode) => (
                                            <span
                                                key={`mob-${professional.slug}-${mode}`}
                                                className="rounded-full bg-stone-100 px-2 py-0.5 text-[11px] font-semibold text-stone-700"
                                            >
                                                {mode}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            {/* Desktop: full-width banner + info below */}
                            <div className="hidden sm:block">
                                <div className="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                                    <div className="aspect-[16/10] bg-stone-100">
                                        <img
                                            src={professional.image}
                                            alt={professional.name}
                                            className="h-full w-full object-cover"
                                        />
                                    </div>
                                </div>

                                <h1 className="mt-6 text-3xl font-bold tracking-tight text-[#3d3429] sm:text-4xl">
                                    {professional.name}
                                </h1>
                                <p className="mt-1 text-base font-semibold text-stone-600">{professional.title}</p>

                                {professional.review_count > 0 && (
                                    <div className="mt-2 flex flex-wrap items-center gap-2">
                                        <StarRow rating={professional.rating} />
                                        <span className="text-sm font-semibold text-stone-700">
                                            {Number(professional.rating).toFixed(1)}
                                        </span>
                                        <span className="text-sm text-stone-500">
                                            ({professional.review_count} {professional.review_count === 1 ? 'review' : 'reviews'})
                                        </span>
                                    </div>
                                )}

                                <div className="mt-3 flex flex-wrap gap-2">
                                    <span className="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100">
                                        {professional.specialty}
                                    </span>
                                    {visitModes.map((mode) => (
                                        <span
                                            key={`${professional.slug}-${mode}`}
                                            className="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-700"
                                        >
                                            {mode}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            {/* About */}
                            <div className="rounded-2xl border border-stone-200 bg-white p-5 sm:p-6">
                                <h2 className="text-sm font-bold uppercase tracking-wide text-stone-500">About</h2>
                                <p className="mt-3 text-base leading-relaxed text-stone-700">{professional.about}</p>
                            </div>

                            {/* Care highlights */}
                            {!!professional.highlights?.length && (
                                <div className="rounded-2xl border border-stone-200 bg-white p-5 sm:p-6">
                                    <h2 className="text-sm font-bold uppercase tracking-wide text-stone-500">
                                        Care highlights
                                    </h2>
                                    <ul className="mt-3 space-y-2.5">
                                        {professional.highlights.map((item) => (
                                            <li key={item} className="flex items-start gap-2 text-sm text-stone-700">
                                                <svg className="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden>
                                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clipRule="evenodd" />
                                                </svg>
                                                {item}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            {/* Details table */}
                            <dl className="space-y-3 rounded-2xl border border-stone-200 bg-white p-5 sm:p-6">
                                <h2 className="text-sm font-bold uppercase tracking-wide text-stone-500">Details</h2>
                                {[
                                    { label: 'Location', value: professional.location },
                                    { label: 'Working hours', value: professional.availability },
                                    { label: 'Next available', value: professional.next_available, highlight: true },
                                    { label: 'Experience', value: professional.experience },
                                    { label: 'Response time', value: professional.response_time },
                                    { label: 'Languages', value: professional.languages?.join(', ') },
                                ].filter(row => row.value).map((row) => (
                                    <div key={row.label} className="flex items-start justify-between gap-4">
                                        <dt className="text-sm font-medium text-stone-500">{row.label}</dt>
                                        <dd className={`text-right text-sm font-semibold ${row.highlight ? 'text-emerald-700' : 'text-stone-900'}`}>
                                            {row.value}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </div>

                        {/* ── Right column: booking panel ── */}
                        <aside className="self-start rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                            <h2 className="text-xl font-bold text-stone-900">Book a consultation</h2>
                            <p className="mt-1 text-sm text-stone-600">Choose a visit type, pick a slot, and request your booking.</p>

                            {/* Visit type toggle */}
                            {visitModes.length > 1 && (
                                <div className="mt-5 flex rounded-xl border border-stone-200 bg-stone-50 p-1">
                                    {visitModes.map((mode) => (
                                        <button
                                            key={mode}
                                            type="button"
                                            onClick={() => {
                                                setVisitType(mode);
                                                setSelectedService(null);
                                            }}
                                            className={`flex-1 rounded-lg py-2 text-xs font-semibold transition ${
                                                visitType === mode
                                                    ? 'bg-white text-stone-900 shadow-sm ring-1 ring-stone-200'
                                                    : 'text-stone-500 hover:text-stone-700'
                                            }`}
                                        >
                                            {mode}
                                        </button>
                                    ))}
                                </div>
                            )}

                            {/* Selectable rate card — filtered by visit type */}
                            <div className="mt-5 space-y-2.5">
                                <p className="text-xs font-bold uppercase tracking-wide text-stone-500">
                                    {visitType ? `${visitType} services` : 'Services'} &amp; rates
                                </p>
                                {filteredServices.length === 0 && (
                                    <p className="text-sm text-stone-500">No services available for this visit type.</p>
                                )}
                                {filteredServices.map((item) => {
                                    const isSelected = selectedService?.service === item.service;
                                    return (
                                        <button
                                            key={`${professional.slug}-${item.service}`}
                                            type="button"
                                            onClick={() => setSelectedService(isSelected ? null : item)}
                                            className={`flex w-full items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left transition ${
                                                isSelected
                                                    ? 'border-[#5c4d3d] bg-[#5c4d3d]/5 ring-1 ring-[#5c4d3d]/30'
                                                    : 'border-stone-200 bg-stone-50 hover:border-stone-300 hover:bg-stone-100'
                                            }`}
                                        >
                                            <span className={`text-sm font-medium ${isSelected ? 'text-[#3d3429]' : 'text-stone-800'}`}>
                                                {item.service}
                                            </span>
                                            <span className={`shrink-0 text-sm font-bold ${isSelected ? 'text-[#5c4d3d]' : 'text-market'}`}>
                                                {item.price}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>

                            {/* Date + time picker */}
                            {!!availableSlots.length && (
                                <div className="mt-6">
                                    <p className="text-xs font-bold uppercase tracking-wide text-stone-500">Available times</p>

                                    {/* Date selector */}
                                    <div className="mt-3 flex gap-2 overflow-x-auto pb-1" style={{ scrollbarWidth: 'none' }}>
                                        {availableSlots.map((day) => (
                                            <button
                                                key={day.date}
                                                type="button"
                                                onClick={() => {
                                                    setSelectedDate(day.date);
                                                    setSelectedTime(null);
                                                    setShowAllTimes(false);
                                                }}
                                                className={`shrink-0 rounded-xl border px-3 py-2 text-center text-xs font-semibold transition ${
                                                    selectedDate === day.date
                                                        ? 'border-[#5c4d3d] bg-[#5c4d3d] text-white'
                                                        : 'border-stone-200 bg-white text-stone-700 hover:border-[#5c4d3d] hover:text-[#5c4d3d]'
                                                }`}
                                            >
                                                <span className="block">{day.date}</span>
                                                <span className={`block font-normal ${selectedDate === day.date ? 'text-white/70' : 'text-stone-400'}`}>
                                                    {day.day !== day.date ? day.day : ''}
                                                </span>
                                            </button>
                                        ))}
                                    </div>

                                    {/* Time slots */}
                                    {selectedDate && activeDay && (
                                        <>
                                            <div className="mt-3 grid grid-cols-2 gap-2">
                                                {visibleTimes?.map((time) => (
                                                    <button
                                                        key={`${professional.slug}-${activeDay.date}-${time}`}
                                                        type="button"
                                                        onClick={() => setSelectedTime(time)}
                                                        className={`rounded-lg border px-3 py-2 text-xs font-semibold transition ${
                                                            selectedTime === time
                                                                ? 'border-market bg-market-muted text-market'
                                                                : 'border-stone-200 bg-white text-stone-700 hover:border-market hover:text-market'
                                                        }`}
                                                    >
                                                        {time}
                                                    </button>
                                                ))}
                                            </div>

                                            {/* More times */}
                                            {!showAllTimes && (activeDay.times?.length ?? 0) > 4 && (
                                                <button
                                                    type="button"
                                                    onClick={() => setShowAllTimes(true)}
                                                    className="mt-2 w-full text-center text-xs font-semibold text-market hover:underline"
                                                >
                                                    More times →
                                                </button>
                                            )}
                                        </>
                                    )}

                                    {selectedDate && selectedTime && (
                                        <div className="mt-3 rounded-lg bg-stone-50 px-3 py-2 text-xs text-stone-500 space-y-0.5">
                                            <p>
                                                <span className="font-semibold text-stone-800">{selectedDate} at {selectedTime}</span>
                                                {visitType && <span className="ml-1 text-stone-400">· {visitType}</span>}
                                            </p>
                                            {selectedService && (
                                                <p>
                                                    <span className="font-semibold text-stone-800">{selectedService.service}</span>
                                                    <span className="ml-1 text-stone-400">· {selectedService.price}</span>
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Booking note */}
                            <div className="mt-6 rounded-xl bg-market-muted px-4 py-3 text-sm text-market">
                                <p className="font-semibold">Booking note</p>
                                <p className="mt-1">{professional.booking_note}</p>
                            </div>

                            <button
                                type="button"
                                disabled={!selectedService || !selectedDate || !selectedTime}
                                className="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-[#5c4d3d] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#4a3e32] disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                {selectedService && selectedDate && selectedTime
                                    ? 'Request booking'
                                    : 'Select a service and time slot'}
                            </button>
                        </aside>
                    </div>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
