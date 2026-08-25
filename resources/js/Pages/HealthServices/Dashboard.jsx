import { Link } from '@inertiajs/react';
import HealthProfessionalLayout from '@/Layouts/HealthProfessionalLayout';
import SeoHead from '@/Components/SeoHead';
import { BookingActionButtons, BookingRow, BookingStatusChip } from '@/Components/Health/BookingActions';
import PaymentDetailsSection from '@/Components/Health/PaymentDetailsSection';

function SetupStep({ done, number, title, body }) {
    return (
        <li className="flex gap-3">
            <span
                className={`mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                    done ? 'bg-emerald-600 text-white' : 'bg-stone-200 text-stone-700'
                }`}
            >
                {done ? '✓' : number}
            </span>
            <div>
                <p className={`text-sm font-semibold ${done ? 'text-emerald-800' : 'text-stone-900'}`}>{title}</p>
                <p className="mt-0.5 text-sm text-stone-600">{body}</p>
            </div>
        </li>
    );
}

function StatTile({ label, value }) {
    return (
        <div className="rounded-2xl border border-stone-200 bg-white px-4 py-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">{label}</p>
            <p className="mt-2 text-2xl font-bold text-stone-900">{value}</p>
        </div>
    );
}

export default function HealthServicesDashboard({
    professional = null,
    payoutDetails = null,
    ghanaBanks = [],
    stats = {},
    next_appointment: nextAppointment = null,
    todays_bookings: todaysBookings = [],
    pending_bookings: pendingBookings = [],
    today_label: todayLabel = '',
    greeting = 'Hello',
}) {
    const needsSetup =
        professional && (professional.services_count === 0 || professional.availability_count === 0);
    const firstName = professional?.name?.split(' ')[0] || 'there';

    return (
        <HealthProfessionalLayout title="Dashboard" professional={professional}>
            <SeoHead
                title="Professional Dashboard"
                description="Manage your healthcare profile, services, and availability."
                url={route('health-professionals.dashboard')}
                image="/images/logo.png"
            />

            {!professional ? (
                <div className="rounded-2xl border border-dashed border-stone-200 bg-white px-6 py-14 text-center">
                    <p className="text-lg font-semibold text-stone-900">No professional profile yet</p>
                    <p className="mt-2 text-sm text-stone-600">
                        Create your healthcare account to manage bookings.
                    </p>
                    <Link
                        href={route('health-professionals.signup')}
                        className="mt-5 inline-flex rounded-lg bg-[#5c4d3d] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#4a3e32]"
                    >
                        Register as a professional
                    </Link>
                </div>
            ) : needsSetup ? (
                <div className="mx-auto max-w-3xl space-y-6">
                    <div>
                        <h1 className="text-2xl font-bold text-stone-900 sm:text-3xl">
                            {greeting}, {firstName}
                        </h1>
                        <p className="mt-2 text-sm leading-relaxed text-stone-600 sm:text-base">
                            Finish setup so families can find you and request appointments. After your services and
                            availability are ready, an admin must approve your profile before it goes live.
                        </p>
                    </div>

                    <section className="rounded-2xl border border-amber-200 bg-amber-50/80 p-5 sm:p-6">
                        <h2 className="text-lg font-bold text-stone-900">Finish setup for admin review</h2>
                        <ol className="mt-5 space-y-4">
                            <SetupStep
                                done
                                number={1}
                                title="Create your account & profile"
                                body="Done — your login and professional details are saved."
                            />
                            <SetupStep
                                done={(professional.visit_modes?.length ?? 0) > 0}
                                number={2}
                                title="Choose visit types"
                                body="Say whether you offer Virtual, In person, or both."
                            />
                            <SetupStep
                                done={professional.services_count > 0}
                                number={3}
                                title="Add services & rates"
                                body="Tell patients what you offer and the price."
                            />
                            <SetupStep
                                done={professional.availability_count > 0}
                                number={4}
                                title="Set weekly availability"
                                body="Choose the days and hours you accept bookings."
                            />
                            <SetupStep
                                done={Boolean(professional.has_payment_details)}
                                number={5}
                                title="Add payment details"
                                body="Tell us where to send your payouts after completed consultations."
                            />
                            <SetupStep
                                done={Boolean(professional.is_publicly_visible)}
                                number={6}
                                title="Get admin approval"
                                body={
                                    professional.approval_status === 'approved'
                                        ? 'Approved — turn on visibility in Settings if you want to appear publicly.'
                                        : professional.approval_status === 'rejected'
                                          ? 'Not approved yet. Update your profile and contact support for a re-review.'
                                          : professional.approval_status === 'suspended'
                                            ? 'Your listing is suspended. Contact support for help.'
                                            : 'Once setup is complete, wait for Mummish to approve your listing.'
                                }
                            />
                        </ol>
                        <Link
                            href={route('health-professionals.edit', professional.id)}
                            className="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-[#5c4d3d] px-4 py-3.5 text-sm font-semibold text-white transition hover:bg-[#4a3e32] sm:w-auto"
                        >
                            Continue setup
                        </Link>
                    </section>

                    <PaymentDetailsSection payoutDetails={payoutDetails} ghanaBanks={ghanaBanks} />
                </div>
            ) : (
                <div className="space-y-6">
                    {!professional.is_publicly_visible && (
                        <div className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                            {professional.approval_status === 'approved'
                                ? 'Your profile is approved but currently hidden. Turn on visibility in Settings to appear on Health Services.'
                                : professional.approval_status === 'rejected'
                                  ? `Your profile was not approved${professional.rejection_reason ? `: ${professional.rejection_reason}` : '.'}`
                                  : professional.approval_status === 'suspended'
                                    ? `Your profile is suspended${professional.rejection_reason ? `: ${professional.rejection_reason}` : '.'}`
                                    : 'Your profile is waiting for admin approval before it appears on Health Services.'}
                        </div>
                    )}
                    <div className="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-bold text-stone-900 sm:text-3xl">
                                {greeting}, {firstName}
                            </h1>
                            <p className="mt-1 text-sm text-stone-600">
                                Here is your schedule and practice overview for today.
                            </p>
                        </div>
                        <span className="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-stone-600 ring-1 ring-stone-200">
                            {todayLabel}
                        </span>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <StatTile label="Pending requests" value={stats.pending_count ?? 0} />
                        <StatTile label="Confirmed today" value={stats.today_confirmed_count ?? 0} />
                        <StatTile label="Confirmed (7 days)" value={stats.upcoming_confirmed_count ?? 0} />
                        <StatTile label="Completed this month" value={stats.completed_month_count ?? 0} />
                    </div>

                    <PaymentDetailsSection payoutDetails={payoutDetails} ghanaBanks={ghanaBanks} />

                    <div className="grid gap-6 lg:grid-cols-5">
                        <div className="space-y-6 lg:col-span-3">
                            <section className="overflow-hidden rounded-2xl bg-[#5c4d3d] p-5 text-white shadow-sm sm:p-6">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-xs font-bold uppercase tracking-wide text-white/70">
                                            Next appointment
                                        </p>
                                        {nextAppointment ? (
                                            <>
                                                <h2 className="mt-2 text-xl font-bold">{nextAppointment.patient_name}</h2>
                                                <p className="mt-1 text-sm text-white/85">
                                                    {nextAppointment.service_name || 'Consultation'}
                                                    {nextAppointment.visit_mode
                                                        ? ` · ${nextAppointment.visit_mode}`
                                                        : ''}
                                                </p>
                                                <p className="mt-3 text-sm font-semibold">
                                                    {nextAppointment.appointment_date_label} ·{' '}
                                                    {nextAppointment.appointment_time}
                                                </p>
                                                <p className="mt-1 text-xs text-white/70">
                                                    {nextAppointment.reference}
                                                    {nextAppointment.patient_phone
                                                        ? ` · ${nextAppointment.patient_phone}`
                                                        : ''}
                                                </p>
                                            </>
                                        ) : (
                                            <p className="mt-3 text-sm text-white/80">
                                                No upcoming appointments right now.
                                            </p>
                                        )}
                                    </div>
                                    {nextAppointment ? (
                                        <BookingStatusChip status={nextAppointment.status} />
                                    ) : null}
                                </div>
                                {nextAppointment ? (
                                    <div className="mt-5">
                                        <BookingActionButtons
                                            professionalId={professional.id}
                                            booking={nextAppointment}
                                            tone="onDark"
                                        />
                                    </div>
                                ) : (
                                    <Link
                                        href={route('health-professionals.schedule')}
                                        className="mt-5 inline-flex rounded-lg bg-white px-3 py-2 text-xs font-semibold text-[#5c4d3d]"
                                    >
                                        Open schedule
                                    </Link>
                                )}
                            </section>

                            <section className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                                <div className="flex items-center justify-between gap-3">
                                    <h2 className="text-lg font-bold text-stone-900">Today&apos;s schedule</h2>
                                    <Link
                                        href={route('health-professionals.schedule')}
                                        className="text-xs font-semibold text-[#5c4d3d] hover:underline"
                                    >
                                        Full schedule
                                    </Link>
                                </div>
                                {todaysBookings.length === 0 ? (
                                    <p className="mt-5 rounded-xl border border-dashed border-stone-200 bg-stone-50 px-4 py-6 text-center text-sm text-stone-500">
                                        No appointments scheduled for today.
                                    </p>
                                ) : (
                                    <ul className="mt-4 space-y-3">
                                        {todaysBookings.map((booking) => (
                                            <li
                                                key={booking.id}
                                                className="flex flex-col gap-3 rounded-xl border border-stone-100 bg-stone-50/80 px-3 py-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
                                            >
                                                <div className="min-w-0">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <span className="text-sm font-bold text-stone-900">
                                                            {booking.appointment_time}
                                                        </span>
                                                        <BookingStatusChip status={booking.status} />
                                                    </div>
                                                    <p className="mt-0.5 text-sm font-semibold text-stone-800">
                                                        {booking.patient_name}
                                                    </p>
                                                    <p className="text-xs text-stone-500">
                                                        {booking.service_name || 'Consultation'}
                                                        {booking.visit_mode ? ` · ${booking.visit_mode}` : ''}
                                                    </p>
                                                </div>
                                                <BookingActionButtons
                                                    professionalId={professional.id}
                                                    booking={booking}
                                                    compact
                                                />
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </section>
                        </div>

                        <div className="space-y-6 lg:col-span-2">
                            <section className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                                <div className="flex items-center justify-between gap-3">
                                    <h2 className="text-lg font-bold text-stone-900">Pending requests</h2>
                                    {(stats.pending_count ?? 0) > 0 ? (
                                        <span className="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-900">
                                            {stats.pending_count}
                                        </span>
                                    ) : null}
                                </div>
                                {pendingBookings.length === 0 ? (
                                    <p className="mt-5 text-sm text-stone-500">No pending requests.</p>
                                ) : (
                                    <ul className="mt-4 space-y-3">
                                        {pendingBookings.slice(0, 5).map((booking) => (
                                            <BookingRow
                                                key={booking.id}
                                                professionalId={professional.id}
                                                booking={booking}
                                            />
                                        ))}
                                    </ul>
                                )}
                            </section>

                            <section className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                                <h2 className="text-lg font-bold text-stone-900">Quick actions</h2>
                                <div className="mt-4 flex flex-col gap-2">
                                    <Link
                                        href={route('health-professionals.schedule')}
                                        className="inline-flex items-center justify-center rounded-lg bg-[#5c4d3d] px-3 py-2.5 text-xs font-semibold text-white transition hover:bg-[#4a3e32]"
                                    >
                                        Open schedule
                                    </Link>
                                    <Link
                                        href={route('health-professionals.edit', professional.id)}
                                        className="inline-flex items-center justify-center rounded-lg border border-stone-200 px-3 py-2.5 text-xs font-semibold text-stone-700 transition hover:border-[#5c4d3d] hover:text-[#5c4d3d]"
                                    >
                                        Edit profile & rates
                                    </Link>
                                    <Link
                                        href={route('health-services.show', professional.slug)}
                                        className="inline-flex items-center justify-center rounded-lg border border-stone-200 px-3 py-2.5 text-xs font-semibold text-stone-700 transition hover:border-[#5c4d3d] hover:text-[#5c4d3d]"
                                    >
                                        View public page
                                    </Link>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            )}
        </HealthProfessionalLayout>
    );
}
