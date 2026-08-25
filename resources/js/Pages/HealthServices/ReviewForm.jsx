import Breadcrumbs from '@/Components/Breadcrumbs';
import InputError from '@/Components/InputError';
import LogoMark from '@/Components/LogoMark';
import SiteFooter from '@/Components/SiteFooter';
import { Head, Link, useForm } from '@inertiajs/react';

function StarPicker({ value, onChange, disabled }) {
    return (
        <div className="flex items-center gap-1" role="radiogroup" aria-label="Star rating">
            {[1, 2, 3, 4, 5].map((star) => (
                <button
                    key={star}
                    type="button"
                    disabled={disabled}
                    onClick={() => onChange(star)}
                    className="rounded p-0.5 transition hover:scale-110 disabled:cursor-not-allowed disabled:opacity-60"
                    aria-label={`${star} star${star === 1 ? '' : 's'}`}
                    aria-pressed={value === star}
                >
                    <svg
                        className={`h-8 w-8 ${star <= value ? 'text-amber-400' : 'text-stone-200'}`}
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        aria-hidden
                    >
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                </button>
            ))}
        </div>
    );
}

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

export default function ReviewForm({ booking, status, error }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        rating: 0,
        comment: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('health-services.bookings.review.store', booking.id), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const displayRating = data.rating;

    return (
        <>
            <Head title={`Review ${booking.professional?.name ?? 'booking'}`} />

            <div className="min-h-screen bg-stone-50 text-stone-900">
                <header className="border-b border-stone-200 bg-white">
                    <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-4 sm:px-6">
                        <LogoMark variant="shop" />
                    </div>
                </header>

                <main className="mx-auto max-w-4xl px-4 py-8 sm:px-6">
                    <Breadcrumbs
                        tone="shop"
                        className="mb-6"
                        items={[
                            { label: 'Home', href: '/' },
                            { label: 'Health Services', href: route('health-services.index') },
                            { label: 'Leave a review', href: route('health-services.bookings.review') },
                            { label: booking.reference },
                        ]}
                    />

                    <div className="rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
                        <h1 className="text-2xl font-bold text-stone-900">Review your visit</h1>
                        <p className="mt-2 text-sm text-stone-600">
                            Booking <span className="font-semibold text-stone-900">{booking.reference}</span>
                            {booking.professional?.name ? (
                                <>
                                    {' '}
                                    with <span className="font-semibold text-stone-900">{booking.professional.name}</span>
                                </>
                            ) : null}
                            {booking.appointment_date ? (
                                <>
                                    {' '}
                                    on {booking.appointment_date} at {booking.appointment_time}
                                </>
                            ) : null}
                            .
                        </p>

                        {status ? (
                            <div className="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                                {status}
                            </div>
                        ) : null}

                        {error ? (
                            <div className="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                                {error}
                            </div>
                        ) : null}

                        {booking.existing_review ? (
                            <article className="mt-8 rounded-xl border border-stone-200 bg-stone-50 p-5">
                                <p className="text-sm font-semibold uppercase tracking-wide text-stone-500">Your review</p>
                                <StarRow rating={booking.existing_review.rating} />
                                {booking.existing_review.comment ? (
                                    <p className="mt-3 text-sm leading-relaxed text-stone-700">{booking.existing_review.comment}</p>
                                ) : null}
                                <p className="mt-3 text-xs text-stone-500">Submitted on {booking.existing_review.date}</p>
                            </article>
                        ) : booking.can_review ? (
                            <form onSubmit={submit} className="mt-8 space-y-6">
                                <div>
                                    <p className="text-sm font-medium text-stone-700">How was your visit?</p>
                                    <div className="mt-2">
                                        <StarPicker
                                            value={displayRating}
                                            onChange={(rating) => setData('rating', rating)}
                                            disabled={processing}
                                        />
                                    </div>
                                    <InputError message={errors.rating} className="mt-2" />
                                </div>

                                <div>
                                    <label htmlFor="comment" className="block text-sm font-medium text-stone-700">
                                        Comments <span className="font-normal text-stone-500">(optional)</span>
                                    </label>
                                    <textarea
                                        id="comment"
                                        name="comment"
                                        rows={4}
                                        value={data.comment}
                                        onChange={(e) => setData('comment', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border border-stone-200 px-3 py-2.5 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                                        placeholder="What went well? Would you recommend this provider?"
                                        maxLength={2000}
                                    />
                                    <InputError message={errors.comment} className="mt-2" />
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing || data.rating < 1}
                                    className="inline-flex rounded-full bg-stone-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-800 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {processing ? 'Submitting…' : 'Submit review'}
                                </button>
                            </form>
                        ) : (
                            <p className="mt-6 text-sm text-stone-600">This booking is not eligible for a review.</p>
                        )}

                        {booking.professional?.slug ? (
                            <p className="mt-8 border-t border-stone-100 pt-6 text-sm text-stone-500">
                                <Link
                                    href={route('health-services.show', booking.professional.slug)}
                                    className="font-semibold text-[#5c4d3d] hover:underline"
                                >
                                    View {booking.professional.name}&apos;s profile
                                </Link>
                            </p>
                        ) : null}
                    </div>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
