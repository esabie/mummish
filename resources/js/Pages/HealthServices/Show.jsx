import { Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import Breadcrumbs from '@/Components/Breadcrumbs';
import InputError from '@/Components/InputError';
import LogoMark from '@/Components/LogoMark';
import Modal from '@/Components/Modal';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';

const inputClass =
    'mt-1 block w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]';

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

function toTime24(label) {
    const [time, meridiem] = String(label).split(' ');
    let [hours, minutes] = time.split(':').map(Number);
    if (meridiem === 'PM' && hours !== 12) {
        hours += 12;
    }
    if (meridiem === 'AM' && hours === 12) {
        hours = 0;
    }
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
}

export default function HealthServicesShow({ professional }) {
    const { flash } = usePage().props;
    const rateCard = useMemo(() => professional.rate_card ?? [], [professional.rate_card]);
    const visitModes = useMemo(() => {
        const configured = professional.visit_modes ?? [];
        const modesWithServices = configured.filter((mode) =>
            rateCard.some((item) => !item.mode || item.mode === mode),
        );

        // Prefer modes that actually have bookable services.
        if (modesWithServices.length > 0) {
            return modesWithServices;
        }

        return configured;
    }, [professional.visit_modes, rateCard]);

    const bookable = Boolean(professional.bookable);
    const [visitType, setVisitType] = useState(visitModes[0] ?? null);

    // Filter out slots in the past. Evaluated fresh each render so stale open tabs also get correct results.
    const availableSlots = useMemo(() => {
        const now = new Date();
        const normalizeTime = (t) =>
            typeof t === 'string' ? { label: t, booked: false } : { label: t.label, booked: Boolean(t.booked) };

        return (professional.slots ?? [])
            .map((day) => {
                const times = (day.times ?? []).map(normalizeTime);
                const isToday = String(day.date).toLowerCase() === 'today';
                const filteredTimes = isToday
                    ? times.filter((slot) => {
                          const [time, meridiem] = slot.label.split(' ');
                          let [hours, minutes] = time.split(':').map(Number);
                          if (meridiem === 'PM' && hours !== 12) hours += 12;
                          if (meridiem === 'AM' && hours === 12) hours = 0;
                          const slotTime = new Date(now);
                          slotTime.setHours(hours, minutes, 0, 0);
                          return slotTime > now;
                      })
                    : times;
                return { ...day, times: filteredTimes };
            })
            .filter((day) => day.times.length > 0);
    }, [professional.slots]);

    const [selectedService, setSelectedService] = useState(null);
    const [selectedDay, setSelectedDay] = useState(null);
    const [selectedTime, setSelectedTime] = useState(null);
    const [showAllTimes, setShowAllTimes] = useState(false);
    const [patientModalOpen, setPatientModalOpen] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        health_professional_service_id: '',
        visit_mode: visitModes[0] ?? 'Virtual',
        appointment_date: '',
        appointment_time: '',
        patient_name: '',
        patient_email: '',
        patient_phone: '',
        notes: '',
    });

    useEffect(() => {
        const patientFieldErrors = [
            errors.patient_name,
            errors.patient_email,
            errors.patient_phone,
            errors.notes,
            errors.health_professional_service_id,
            errors.appointment_date,
            errors.appointment_time,
            errors.visit_mode,
        ].some(Boolean);

        if (patientFieldErrors) {
            setPatientModalOpen(true);
        }
    }, [errors]);

    const filteredServices = useMemo(
        () =>
            rateCard.filter((item) => !item.mode || !visitType || item.mode === visitType),
        [rateCard, visitType],
    );
    const hasServicesForVisitType = filteredServices.length > 0;

    useEffect(() => {
        if (!visitModes.length) {
            return;
        }
        if (!visitType || !visitModes.includes(visitType)) {
            setVisitType(visitModes[0]);
        }
    }, [visitType, visitModes]);

    useEffect(() => {
        if (!hasServicesForVisitType) {
            setSelectedService(null);
            setSelectedDay(null);
            setSelectedTime(null);
            setShowAllTimes(false);
            return;
        }

        // Keep selection if it still matches this visit type; otherwise pick the only option or clear.
        const stillValid =
            selectedService &&
            filteredServices.some(
                (item) =>
                    item.id === selectedService.id &&
                    item.service === selectedService.service &&
                    item.mode === selectedService.mode,
            );

        if (stillValid) {
            return;
        }

        if (filteredServices.length === 1) {
            setSelectedService(filteredServices[0]);
            return;
        }

        setSelectedService(null);
    }, [hasServicesForVisitType, visitType, filteredServices, selectedService]);

    const visibleTimes = showAllTimes ? selectedDay?.times : selectedDay?.times?.slice(0, 4);
    const canRequest =
        bookable &&
        hasServicesForVisitType &&
        selectedService?.id &&
        selectedDay?.date_iso &&
        selectedTime;

    const requestButtonLabel = (() => {
        if (canRequest) {
            return 'Continue to payment';
        }
        if (!hasServicesForVisitType) {
            return 'No services available';
        }
        if (!selectedService?.id) {
            return 'Select a service';
        }
        if (!selectedDay?.date_iso || !selectedTime) {
            return 'Select a time slot';
        }
        return 'Select a service and time slot';
    })();

    const openPatientModal = () => {
        if (!canRequest) {
            return;
        }

        clearErrors();
        setData({
            ...data,
            health_professional_service_id: selectedService.id,
            visit_mode: visitType || selectedService.mode || 'Virtual',
            appointment_date: selectedDay.date_iso,
            appointment_time: toTime24(selectedTime),
        });
        setPatientModalOpen(true);
    };

    const submitBooking = (e) => {
        e.preventDefault();
        post(route('health-services.bookings.store', professional.slug), {
            onSuccess: () => {
                setPatientModalOpen(false);
                reset('patient_name', 'patient_email', 'patient_phone', 'notes');
                setSelectedService(null);
                setSelectedDay(null);
                setSelectedTime(null);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
        });
    };

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
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-3 sm:gap-3 sm:px-6 sm:py-4 lg:px-8">
                        <Link
                            href={route('health-services.index')}
                            className="shrink-0 text-sm font-semibold text-[#5c4d3d] hover:text-market hover:underline"
                        >
                            <span className="sm:hidden">← Back</span>
                            <span className="hidden sm:inline">← Back to Health Services</span>
                        </Link>
                        <LogoMark variant="shop" className="min-w-0 flex-1 justify-center" />
                        <Link
                            href={route('shop.index')}
                            className="shrink-0 rounded-full border border-stone-200 px-2.5 py-1.5 text-xs font-semibold text-stone-700 transition hover:border-market hover:text-market sm:px-3"
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

                <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-8 pb-28 sm:px-6 sm:py-10 lg:px-8 lg:pb-10">
                    {flash?.success ? (
                        <div className="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                            {flash.success}
                        </div>
                    ) : null}
                    {flash?.error ? (
                        <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                            {flash.error}
                        </div>
                    ) : null}

                    <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.85fr)] lg:gap-12">

                        {/* ── Left column ── */}
                        <div className="space-y-6">

                            {/* Mobile: compact side-by-side layout */}
                            <div className="flex gap-4 sm:hidden">
                                    <div className="h-28 w-28 shrink-0 overflow-hidden rounded-2xl bg-stone-100">
                                    <img
                                        src={professional.image}
                                        alt={professional.name}
                                        className="h-full w-full object-cover object-center"
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

                            {/* Desktop: square headshot matching crop upload */}
                            <div className="hidden sm:block">
                                <div className="max-w-md overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                                    <div className="aspect-square bg-stone-100">
                                        <img
                                            src={professional.image}
                                            alt={professional.name}
                                            className="h-full w-full object-cover object-center"
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

                            {/* Reviews */}
                            {!!professional.reviews?.length && (
                                <div className="rounded-2xl border border-stone-200 bg-white p-5 sm:p-6">
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h2 className="text-sm font-bold uppercase tracking-wide text-stone-500">
                                                Patient reviews
                                            </h2>
                                            <p className="mt-1 text-sm text-stone-600">
                                                Feedback from families after completed visits.
                                            </p>
                                        </div>
                                        <Link
                                            href={route('health-services.bookings.review')}
                                            className="shrink-0 text-sm font-semibold text-[#5c4d3d] hover:underline"
                                        >
                                            Leave a review
                                        </Link>
                                    </div>

                                    <div className="mt-5 space-y-4">
                                        {professional.reviews.map((review, idx) => (
                                            <article
                                                key={`${review.author}-${idx}`}
                                                className="rounded-xl border border-stone-100 bg-stone-50 p-4"
                                            >
                                                <StarRow rating={review.rating} />
                                                {review.comment ? (
                                                    <p className="mt-2 text-sm leading-relaxed text-stone-700">{review.comment}</p>
                                                ) : null}
                                                <div className="mt-3 flex items-center gap-3">
                                                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#5c4d3d] text-xs font-bold text-white">
                                                        {review.initial}
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-semibold text-stone-900">{review.author}</p>
                                                        <p className="text-xs text-stone-500">
                                                            Verified visit{review.date ? ` · ${review.date}` : ''}
                                                        </p>
                                                    </div>
                                                </div>
                                            </article>
                                        ))}
                                    </div>
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
                        <aside
                            id="book-panel"
                            className="self-start rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6"
                        >
                            <h2 className="text-xl font-bold text-stone-900">Book a consultation</h2>
                            <p className="mt-1 text-sm text-stone-600">
                                Choose a visit type, pick a slot, and request your booking.
                            </p>

                            {!bookable ? (
                                <div className="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                                    Online booking isn&apos;t available for this profile yet. Please check back soon.
                                </div>
                            ) : null}

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
                                                setSelectedDay(null);
                                                setSelectedTime(null);
                                                setShowAllTimes(false);
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
                                {!hasServicesForVisitType && (
                                    <p className="text-sm text-stone-500">
                                        No services available for this visit type.
                                        {visitModes.length > 1
                                            ? ' Try switching visit type above.'
                                            : ''}
                                    </p>
                                )}
                                {filteredServices.map((item) => {
                                    const isSelected =
                                        selectedService?.service === item.service &&
                                        selectedService?.mode === item.mode;
                                    return (
                                        <button
                                            key={`${professional.slug}-${item.id ?? item.service}-${item.mode}`}
                                            type="button"
                                            onClick={() => setSelectedService(isSelected ? null : item)}
                                            className={`flex w-full items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left transition ${
                                                isSelected
                                                    ? 'border-[#5c4d3d] bg-[#5c4d3d]/5 ring-1 ring-[#5c4d3d]/30'
                                                    : 'border-stone-200 bg-stone-50 hover:border-stone-300 hover:bg-stone-100'
                                            }`}
                                        >
                                            <span
                                                className={`text-sm font-medium ${isSelected ? 'text-[#3d3429]' : 'text-stone-800'}`}
                                            >
                                                {item.service}
                                            </span>
                                            <span
                                                className={`shrink-0 text-sm font-bold ${isSelected ? 'text-[#5c4d3d]' : 'text-market'}`}
                                            >
                                                {item.price}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>

                            {/* Date + time picker — only when this visit type has services */}
                            {hasServicesForVisitType && !!availableSlots.length && (
                                <div className="mt-6">
                                    <p className="text-xs font-bold uppercase tracking-wide text-stone-500">Available times</p>

                                    <div className="mt-3 flex gap-2 overflow-x-auto pb-1" style={{ scrollbarWidth: 'none' }}>
                                        {availableSlots.map((day) => (
                                            <button
                                                key={day.date_iso || day.date}
                                                type="button"
                                                onClick={() => {
                                                    setSelectedDay(day);
                                                    setSelectedTime(null);
                                                    setShowAllTimes(false);
                                                }}
                                                className={`shrink-0 rounded-xl border px-3 py-2 text-center text-xs font-semibold transition ${
                                                    selectedDay?.date_iso === day.date_iso && selectedDay?.date === day.date
                                                        ? 'border-[#5c4d3d] bg-[#5c4d3d] text-white'
                                                        : 'border-stone-200 bg-white text-stone-700 hover:border-[#5c4d3d] hover:text-[#5c4d3d]'
                                                }`}
                                            >
                                                <span className="block">{day.date}</span>
                                                <span
                                                    className={`block font-normal ${
                                                        selectedDay?.date_iso === day.date_iso && selectedDay?.date === day.date
                                                            ? 'text-white/70'
                                                            : 'text-stone-400'
                                                    }`}
                                                >
                                                    {day.day !== day.date ? day.day : ''}
                                                </span>
                                            </button>
                                        ))}
                                    </div>

                                    {selectedDay && (
                                        <>
                                            <div className="mt-3 grid grid-cols-2 gap-2">
                                                {visibleTimes?.map((slot) => {
                                                    const time = slot.label;
                                                    const isBooked = Boolean(slot.booked);
                                                    const isSelected = !isBooked && selectedTime === time;

                                                    return (
                                                        <button
                                                            key={`${professional.slug}-${selectedDay.date_iso || selectedDay.date}-${time}`}
                                                            type="button"
                                                            disabled={isBooked}
                                                            onClick={() => {
                                                                if (!isBooked) {
                                                                    setSelectedTime(time);
                                                                }
                                                            }}
                                                            className={`rounded-lg border px-3 py-2 text-xs font-semibold transition ${
                                                                isBooked
                                                                    ? 'cursor-not-allowed border-stone-200 bg-stone-100 text-stone-400 line-through'
                                                                    : isSelected
                                                                      ? 'border-market bg-market-muted text-market'
                                                                      : 'border-stone-200 bg-white text-stone-700 hover:border-market hover:text-market'
                                                            }`}
                                                        >
                                                            {time}
                                                            {isBooked ? (
                                                                <span className="mt-0.5 block text-[10px] font-medium no-underline">
                                                                    Booked
                                                                </span>
                                                            ) : null}
                                                        </button>
                                                    );
                                                })}
                                            </div>

                                            {!showAllTimes && (selectedDay.times?.length ?? 0) > 4 && (
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

                                    {selectedDay && selectedTime && (
                                        <div className="mt-3 space-y-0.5 rounded-lg bg-stone-50 px-3 py-2 text-xs text-stone-500">
                                            <p>
                                                <span className="font-semibold text-stone-800">
                                                    {selectedDay.date} at {selectedTime}
                                                </span>
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

                            {bookable && hasServicesForVisitType && availableSlots.length === 0 ? (
                                <p className="mt-5 text-sm text-stone-500">No upcoming times are available right now.</p>
                            ) : null}

                            {/* Booking note */}
                            <div className="mt-6 rounded-xl bg-market-muted px-4 py-3 text-sm text-market">
                                <p className="font-semibold">Booking note</p>
                                <p className="mt-1">{professional.booking_note}</p>
                            </div>

                            <button
                                type="button"
                                disabled={!canRequest}
                                onClick={openPatientModal}
                                className="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-[#5c4d3d] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#4a3e32] disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                {requestButtonLabel}
                            </button>
                        </aside>
                    </div>
                </main>

                <SiteFooter />

                {bookable ? (
                    <div className="fixed inset-x-0 bottom-0 z-30 border-t border-stone-200/90 bg-white/95 px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-4px_20px_rgba(0,0,0,0.06)] backdrop-blur lg:hidden">
                        <button
                            type="button"
                            disabled={!canRequest}
                            onClick={() => {
                                if (canRequest) {
                                    openPatientModal();
                                    return;
                                }
                                document.getElementById('book-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }}
                            className="inline-flex w-full items-center justify-center rounded-xl bg-[#5c4d3d] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#4a3e32] disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            {canRequest ? requestButtonLabel : 'Choose a time to book'}
                        </button>
                    </div>
                ) : null}
            </div>

            <Modal
                show={patientModalOpen}
                onClose={() => {
                    if (!processing) {
                        setPatientModalOpen(false);
                    }
                }}
                maxWidth="md"
                closeable={!processing}
            >
                <form onSubmit={submitBooking} className="px-4 py-5 sm:px-6 sm:py-6">
                    <h2 className="text-lg font-bold text-stone-900">Pay to reserve</h2>
                    <p className="mt-1 text-sm text-stone-600">
                        Enter your details, then pay securely to reserve this slot with {professional.name}.
                    </p>

                    <div className="mt-4 rounded-lg bg-stone-50 px-3 py-2 text-xs text-stone-600">
                        <p className="font-semibold text-stone-800">
                            {selectedService?.service} · {selectedDay?.date} at {selectedTime}
                        </p>
                        <p className="mt-0.5">
                            {visitType}
                            {selectedService?.price ? ` · ${selectedService.price}` : ''}
                        </p>
                        <p className="mt-1 text-stone-500">
                            You&apos;ll be redirected to Paystack to complete payment. The slot is held briefly while you pay.
                        </p>
                    </div>

                    <div className="mt-5 space-y-4">
                        <div>
                            <label htmlFor="patient_name" className="text-sm font-medium text-stone-700">
                                Full name <span className="text-red-600">*</span>
                            </label>
                            <input
                                id="patient_name"
                                type="text"
                                className={inputClass}
                                value={data.patient_name}
                                onChange={(e) => setData('patient_name', e.target.value)}
                                autoComplete="name"
                                required
                            />
                            <InputError message={errors.patient_name} className="mt-1" />
                        </div>
                        <div>
                            <label htmlFor="patient_email" className="text-sm font-medium text-stone-700">
                                Email <span className="text-red-600">*</span>
                            </label>
                            <input
                                id="patient_email"
                                type="email"
                                className={inputClass}
                                value={data.patient_email}
                                onChange={(e) => setData('patient_email', e.target.value)}
                                autoComplete="email"
                                required
                            />
                            <InputError message={errors.patient_email} className="mt-1" />
                        </div>
                        <div>
                            <label htmlFor="patient_phone" className="text-sm font-medium text-stone-700">
                                Phone <span className="text-red-600">*</span>
                            </label>
                            <input
                                id="patient_phone"
                                type="tel"
                                className={inputClass}
                                value={data.patient_phone}
                                onChange={(e) => setData('patient_phone', e.target.value)}
                                autoComplete="tel"
                                placeholder="e.g. 024 123 4567"
                                required
                            />
                            <InputError message={errors.patient_phone} className="mt-1" />
                        </div>
                        <div>
                            <label htmlFor="notes" className="text-sm font-medium text-stone-700">
                                Notes <span className="text-stone-400">(optional)</span>
                            </label>
                            <textarea
                                id="notes"
                                rows="3"
                                className={inputClass}
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                placeholder="Anything the provider should know before your visit"
                            />
                            <InputError message={errors.notes} className="mt-1" />
                        </div>
                        <InputError
                            message={
                                errors.health_professional_service_id ||
                                errors.appointment_date ||
                                errors.appointment_time ||
                                errors.visit_mode
                            }
                            className="mt-1"
                        />
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            disabled={processing}
                            onClick={() => setPatientModalOpen(false)}
                            className="inline-flex items-center justify-center rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center justify-center rounded-lg bg-[#5c4d3d] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#4a3e32] disabled:opacity-50"
                        >
                            {processing ? 'Redirecting to payment…' : `Pay ${selectedService?.price || ''} & reserve`}
                        </button>
                    </div>
                </form>
            </Modal>
        </>
    );
}
