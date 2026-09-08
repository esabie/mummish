import { useForm } from '@inertiajs/react';
import HealthProfessionalLayout from '@/Layouts/HealthProfessionalLayout';
import SeoHead from '@/Components/SeoHead';
import InputError from '@/Components/InputError';
import { BookingStatusChip } from '@/Components/Health/BookingActions';

export default function BookingLookup({ professional = null, result = null }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        reference: '',
        patient_email: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('health-professionals.bookings.lookup.submit'), {
            preserveScroll: true,
        });
    };

    return (
        <HealthProfessionalLayout title="Find booking" professional={professional}>
            <SeoHead
                title="Find booking"
                description="Look up one of your patient bookings by reference and email."
                url={route('health-professionals.bookings.lookup')}
            />

            <div className="mx-auto max-w-3xl space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-stone-900 sm:text-3xl">Find a booking</h1>
                    <p className="mt-2 text-sm text-stone-600 sm:text-base">
                        Search your own bookings with the patient&apos;s reference and email.
                    </p>
                </div>

                <form
                    onSubmit={submit}
                    className="space-y-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6"
                >
                    <div>
                        <label htmlFor="reference" className="block text-sm font-medium text-stone-700">
                            Booking reference
                        </label>
                        <input
                            id="reference"
                            type="text"
                            value={data.reference}
                            onChange={(e) => setData('reference', e.target.value)}
                            placeholder="e.g. HB-A1B2C3"
                            className="mt-1 block w-full rounded-lg border border-stone-200 px-3 py-2.5 text-sm shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                            required
                        />
                        <InputError message={errors.reference} className="mt-1" />
                    </div>
                    <div>
                        <label htmlFor="patient_email" className="block text-sm font-medium text-stone-700">
                            Patient email
                        </label>
                        <input
                            id="patient_email"
                            type="email"
                            value={data.patient_email}
                            onChange={(e) => setData('patient_email', e.target.value)}
                            placeholder="patient@example.com"
                            className="mt-1 block w-full rounded-lg border border-stone-200 px-3 py-2.5 text-sm shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                            required
                        />
                        <InputError message={errors.patient_email} className="mt-1" />
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-[#5c4d3d] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#4a3e32] disabled:opacity-50"
                        >
                            {processing ? 'Searching…' : 'Look up'}
                        </button>
                        {result ? (
                            <button
                                type="button"
                                onClick={() => reset()}
                                className="rounded-lg border border-stone-200 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50"
                            >
                                Clear form
                            </button>
                        ) : null}
                    </div>
                </form>

                {result ? (
                    <section className="space-y-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="font-mono text-lg font-bold text-stone-900">{result.reference}</h2>
                            <BookingStatusChip status={result.status} />
                        </div>
                        <dl className="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-xs font-semibold uppercase tracking-wide text-stone-500">Patient</dt>
                                <dd className="mt-1 font-medium text-stone-900">{result.patient_name}</dd>
                                <dd className="text-stone-600">{result.patient_email}</dd>
                                {result.patient_phone ? <dd className="text-stone-600">{result.patient_phone}</dd> : null}
                            </div>
                            <div>
                                <dt className="text-xs font-semibold uppercase tracking-wide text-stone-500">Appointment</dt>
                                <dd className="mt-1 font-medium text-stone-900">
                                    {result.appointment_date_label} · {result.appointment_time}
                                </dd>
                                <dd className="text-stone-600">
                                    {result.service_name || 'Consultation'}
                                    {result.visit_mode ? ` · ${result.visit_mode}` : ''}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs font-semibold uppercase tracking-wide text-stone-500">Payment</dt>
                                <dd className="mt-1 font-medium text-stone-900">{result.payment_status_label}</dd>
                                <dd className="text-stone-600">{result.amount_label}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-semibold uppercase tracking-wide text-stone-500">Logistics</dt>
                                <dd className="mt-1 text-stone-700">
                                    {result.meeting_location || result.join_url || '—'}
                                </dd>
                                {result.meeting_whatsapp ? (
                                    <dd className="text-stone-600">WhatsApp: {result.meeting_whatsapp}</dd>
                                ) : null}
                            </div>
                        </dl>
                        {result.logistics_notes ? (
                            <p className="text-sm text-stone-600">Notes: {result.logistics_notes}</p>
                        ) : null}
                        {result.join_url ? (
                            <a
                                href={result.join_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className={`inline-flex rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition ${
                                    result.can_join_session
                                        ? 'bg-emerald-700 hover:bg-emerald-800'
                                        : 'bg-stone-400 hover:bg-stone-500'
                                }`}
                            >
                                {result.can_join_session ? 'Start Session' : 'Open session link'}
                            </a>
                        ) : result.can_add_meeting_url ? (
                            <p className="text-sm text-amber-800">
                                No Google Meet link yet — open the booking on your dashboard and click Add Google Meet
                                link.
                            </p>
                        ) : null}
                    </section>
                ) : null}
            </div>
        </HealthProfessionalLayout>
    );
}
