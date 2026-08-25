import Breadcrumbs from '@/Components/Breadcrumbs';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import LogoMark from '@/Components/LogoMark';
import SiteFooter from '@/Components/SiteFooter';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function ReviewLookup({ prefill, error }) {
    const { data, setData, post, processing, errors } = useForm({
        reference: prefill?.reference ?? '',
        patient_email: prefill?.patient_email ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('health-services.bookings.review.lookup'));
    };

    return (
        <>
            <Head title="Leave a review" />

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
                            { label: 'Leave a review' },
                        ]}
                    />

                    <div className="rounded-2xl border border-stone-200 bg-white p-8 shadow-sm">
                        <h1 className="text-2xl font-bold text-stone-900">Leave a review</h1>
                        <p className="mt-2 text-sm leading-relaxed text-stone-600">
                            After your visit, share feedback about your health professional. Enter your booking
                            reference and the email you used when booking.
                        </p>

                        {error ? (
                            <div className="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                                {error}
                            </div>
                        ) : null}

                        <form onSubmit={submit} className="mt-8 space-y-5">
                            <div>
                                <InputLabel htmlFor="reference" value="Booking reference" />
                                <TextInput
                                    id="reference"
                                    name="reference"
                                    value={data.reference}
                                    onChange={(e) => setData('reference', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="e.g. HB-ABC123"
                                    autoComplete="off"
                                    required
                                />
                                <InputError message={errors.reference} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="patient_email" value="Email address" />
                                <TextInput
                                    id="patient_email"
                                    type="email"
                                    name="patient_email"
                                    value={data.patient_email}
                                    onChange={(e) => setData('patient_email', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoComplete="email"
                                    required
                                />
                                <InputError message={errors.patient_email} className="mt-2" />
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex rounded-full bg-stone-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-800 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {processing ? 'Looking up…' : 'Find booking'}
                            </button>
                        </form>

                        <p className="mt-8 border-t border-stone-100 pt-6 text-sm text-stone-500">
                            Browse providers on{' '}
                            <Link href={route('health-services.index')} className="font-semibold text-[#5c4d3d] hover:underline">
                                Health Services
                            </Link>
                            .
                        </p>
                    </div>
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
