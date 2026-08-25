import { useMemo, useState } from 'react';
import HealthProfessionalLayout from '@/Layouts/HealthProfessionalLayout';
import SeoHead from '@/Components/SeoHead';
import { BookingRow } from '@/Components/Health/BookingActions';

const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

function pad2(n) {
    return String(n).padStart(2, '0');
}

function toIsoDate(year, monthIndex, day) {
    return `${year}-${pad2(monthIndex + 1)}-${pad2(day)}`;
}

function parseIso(iso) {
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d);
}

function formatMonthLabel(year, monthIndex) {
    return new Date(year, monthIndex, 1).toLocaleDateString(undefined, {
        month: 'long',
        year: 'numeric',
    });
}

function formatDayHeading(iso) {
    return parseIso(iso).toLocaleDateString(undefined, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function buildMonthCells(year, monthIndex) {
    const first = new Date(year, monthIndex, 1);
    // Monday-first: JS getDay() Sunday=0 → shift so Monday=0
    const startOffset = (first.getDay() + 6) % 7;
    const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();
    const cells = [];

    for (let i = 0; i < startOffset; i += 1) {
        cells.push(null);
    }

    for (let day = 1; day <= daysInMonth; day += 1) {
        cells.push({
            day,
            iso: toIsoDate(year, monthIndex, day),
        });
    }

    while (cells.length % 7 !== 0) {
        cells.push(null);
    }

    return cells;
}

function groupByDate(bookings) {
    const groups = {};
    for (const booking of bookings) {
        const key = booking.appointment_date;
        if (!groups[key]) {
            groups[key] = {
                date: key,
                label: booking.appointment_date_label,
                items: [],
            };
        }
        groups[key].items.push(booking);
    }
    return Object.values(groups);
}

function daySummary(bookingsForDay) {
    const pending = bookingsForDay.filter((b) => b.status === 'pending').length;
    const confirmed = bookingsForDay.filter((b) => b.status === 'confirmed').length;
    const other = bookingsForDay.length - pending - confirmed;

    return { total: bookingsForDay.length, pending, confirmed, other };
}

export default function HealthServicesSchedule({
    professional = null,
    bookings = [],
    pending_bookings: pendingBookings = [],
    upcoming = [],
    recent = [],
    today = null,
}) {
    const todayIso = today || new Date().toISOString().slice(0, 10);
    const initial = parseIso(todayIso);

    const [viewMode, setViewMode] = useState('calendar');
    const [cursorYear, setCursorYear] = useState(initial.getFullYear());
    const [cursorMonth, setCursorMonth] = useState(initial.getMonth());
    const [selectedDate, setSelectedDate] = useState(todayIso);

    const bookingsByDate = useMemo(() => {
        const map = {};
        for (const booking of bookings) {
            const key = booking.appointment_date;
            if (!map[key]) {
                map[key] = [];
            }
            map[key].push(booking);
        }
        return map;
    }, [bookings]);

    const monthCells = useMemo(
        () => buildMonthCells(cursorYear, cursorMonth),
        [cursorYear, cursorMonth],
    );

    const selectedBookings = bookingsByDate[selectedDate] ?? [];
    const upcomingGroups = useMemo(() => groupByDate(upcoming), [upcoming]);
    const recentGroups = useMemo(() => groupByDate(recent), [recent]);

    const shiftMonth = (delta) => {
        const next = new Date(cursorYear, cursorMonth + delta, 1);
        setCursorYear(next.getFullYear());
        setCursorMonth(next.getMonth());
    };

    const goToToday = () => {
        const d = parseIso(todayIso);
        setCursorYear(d.getFullYear());
        setCursorMonth(d.getMonth());
        setSelectedDate(todayIso);
    };

    return (
        <HealthProfessionalLayout title="Schedule" professional={professional}>
            <SeoHead
                title="Schedule | Health Professional"
                description="Manage upcoming and recent patient bookings."
                url={route('health-professionals.schedule')}
                image="/images/logo.png"
            />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold text-stone-900 sm:text-3xl">Schedule</h1>
                        <p className="mt-1 text-sm text-stone-600">
                            See your month at a glance, then confirm or update each booking.
                        </p>
                    </div>
                    <div className="inline-flex rounded-xl border border-stone-200 bg-white p-1 shadow-sm">
                        {[
                            { id: 'calendar', label: 'Calendar' },
                            { id: 'list', label: 'List' },
                        ].map((mode) => (
                            <button
                                key={mode.id}
                                type="button"
                                onClick={() => setViewMode(mode.id)}
                                className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition ${
                                    viewMode === mode.id
                                        ? 'bg-[#5c4d3d] text-white'
                                        : 'text-stone-600 hover:bg-stone-50'
                                }`}
                            >
                                {mode.label}
                            </button>
                        ))}
                    </div>
                </div>

                {pendingBookings.length > 0 ? (
                    <section className="rounded-2xl border border-amber-200 bg-amber-50/80 p-4 sm:p-5">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 className="text-sm font-bold text-amber-950">Pending requests</h2>
                                <p className="mt-0.5 text-xs text-amber-900/80">
                                    Needs your confirm or decline — {pendingBookings.length} waiting.
                                </p>
                            </div>
                        </div>
                        <ul className="mt-3 space-y-2">
                            {pendingBookings.slice(0, 4).map((booking) => (
                                <BookingRow
                                    key={booking.id}
                                    professionalId={professional?.id}
                                    booking={booking}
                                />
                            ))}
                        </ul>
                        {pendingBookings.length > 4 ? (
                            <p className="mt-2 text-xs text-amber-900/70">
                                +{pendingBookings.length - 4} more in the calendar / list.
                            </p>
                        ) : null}
                    </section>
                ) : null}

                {viewMode === 'calendar' ? (
                    <div className="grid gap-6 lg:grid-cols-5">
                        <section className="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm sm:p-5 lg:col-span-3">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <h2 className="text-lg font-bold text-stone-900">
                                    {formatMonthLabel(cursorYear, cursorMonth)}
                                </h2>
                                <div className="flex items-center gap-1">
                                    <button
                                        type="button"
                                        onClick={goToToday}
                                        className="rounded-lg border border-stone-200 px-2.5 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-50"
                                    >
                                        Today
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => shiftMonth(-1)}
                                        className="rounded-lg border border-stone-200 px-2.5 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-50"
                                        aria-label="Previous month"
                                    >
                                        ←
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => shiftMonth(1)}
                                        className="rounded-lg border border-stone-200 px-2.5 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-50"
                                        aria-label="Next month"
                                    >
                                        →
                                    </button>
                                </div>
                            </div>

                            <div className="mt-4 grid grid-cols-7 gap-1 text-center text-[11px] font-semibold uppercase tracking-wide text-stone-400">
                                {WEEKDAYS.map((day) => (
                                    <div key={day} className="py-1">
                                        {day}
                                    </div>
                                ))}
                            </div>

                            <div className="mt-1 grid grid-cols-7 gap-1">
                                {monthCells.map((cell, index) => {
                                    if (!cell) {
                                        return <div key={`empty-${index}`} className="min-h-[4.5rem]" />;
                                    }

                                    const dayBookings = bookingsByDate[cell.iso] ?? [];
                                    const summary = daySummary(dayBookings);
                                    const isSelected = selectedDate === cell.iso;
                                    const isToday = cell.iso === todayIso;

                                    return (
                                        <button
                                            key={cell.iso}
                                            type="button"
                                            onClick={() => setSelectedDate(cell.iso)}
                                            className={`flex min-h-[4.5rem] flex-col rounded-xl border p-1.5 text-left transition sm:p-2 ${
                                                isSelected
                                                    ? 'border-[#5c4d3d] bg-[#5c4d3d]/5 ring-1 ring-[#5c4d3d]/30'
                                                    : isToday
                                                      ? 'border-[#5c4d3d]/40 bg-white hover:border-[#5c4d3d]'
                                                      : 'border-stone-100 bg-stone-50/60 hover:border-stone-300 hover:bg-white'
                                            }`}
                                        >
                                            <span
                                                className={`inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold ${
                                                    isToday
                                                        ? 'bg-[#5c4d3d] text-white'
                                                        : 'text-stone-800'
                                                }`}
                                            >
                                                {cell.day}
                                            </span>
                                            {summary.total > 0 ? (
                                                <div className="mt-auto flex flex-wrap gap-0.5 pt-1">
                                                    {summary.pending > 0 ? (
                                                        <span className="h-1.5 w-1.5 rounded-full bg-amber-500" title={`${summary.pending} pending`} />
                                                    ) : null}
                                                    {summary.confirmed > 0 ? (
                                                        <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" title={`${summary.confirmed} confirmed`} />
                                                    ) : null}
                                                    {summary.other > 0 ? (
                                                        <span className="h-1.5 w-1.5 rounded-full bg-stone-400" title={`${summary.other} other`} />
                                                    ) : null}
                                                    <span className="ml-0.5 text-[10px] font-semibold text-stone-500">
                                                        {summary.total}
                                                    </span>
                                                </div>
                                            ) : null}
                                        </button>
                                    );
                                })}
                            </div>

                            <div className="mt-4 flex flex-wrap gap-3 text-[11px] text-stone-500">
                                <span className="inline-flex items-center gap-1.5">
                                    <span className="h-1.5 w-1.5 rounded-full bg-amber-500" /> Pending
                                </span>
                                <span className="inline-flex items-center gap-1.5">
                                    <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" /> Confirmed
                                </span>
                                <span className="inline-flex items-center gap-1.5">
                                    <span className="h-1.5 w-1.5 rounded-full bg-stone-400" /> Cancelled / completed
                                </span>
                            </div>
                        </section>

                        <section className="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm sm:p-5 lg:col-span-2">
                            <h2 className="text-lg font-bold text-stone-900">{formatDayHeading(selectedDate)}</h2>
                            <p className="mt-1 text-xs text-stone-500">
                                {selectedBookings.length === 0
                                    ? 'No bookings on this day.'
                                    : `${selectedBookings.length} booking${selectedBookings.length === 1 ? '' : 's'}`}
                            </p>

                            {selectedBookings.length === 0 ? (
                                <p className="mt-6 rounded-xl border border-dashed border-stone-200 bg-stone-50 px-4 py-8 text-center text-sm text-stone-500">
                                    Select another day, or switch to List for upcoming appointments.
                                </p>
                            ) : (
                                <ul className="mt-4 space-y-3">
                                    {selectedBookings.map((booking) => (
                                        <BookingRow
                                            key={booking.id}
                                            professionalId={professional?.id}
                                            booking={booking}
                                        />
                                    ))}
                                </ul>
                            )}
                        </section>
                    </div>
                ) : (
                    <div className="space-y-8">
                        <section>
                            <h2 className="text-sm font-bold uppercase tracking-wide text-stone-500">Upcoming</h2>
                            {upcomingGroups.length === 0 ? (
                                <p className="mt-3 rounded-xl border border-dashed border-stone-200 bg-white px-4 py-8 text-center text-sm text-stone-500">
                                    No upcoming bookings.
                                </p>
                            ) : (
                                <div className="mt-3 space-y-6">
                                    {upcomingGroups.map((group) => (
                                        <div key={group.date}>
                                            <h3 className="mb-2 text-sm font-semibold text-stone-800">{group.label}</h3>
                                            <ul className="space-y-3">
                                                {group.items.map((booking) => (
                                                    <BookingRow
                                                        key={booking.id}
                                                        professionalId={professional?.id}
                                                        booking={booking}
                                                    />
                                                ))}
                                            </ul>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </section>

                        <section>
                            <h2 className="text-sm font-bold uppercase tracking-wide text-stone-500">
                                Recent (cancelled &amp; completed)
                            </h2>
                            {recentGroups.length === 0 ? (
                                <p className="mt-3 rounded-xl border border-dashed border-stone-200 bg-white px-4 py-6 text-center text-sm text-stone-500">
                                    No recent cancelled or completed bookings.
                                </p>
                            ) : (
                                <div className="mt-3 space-y-6">
                                    {recentGroups.map((group) => (
                                        <div key={group.date}>
                                            <h3 className="mb-2 text-sm font-semibold text-stone-800">{group.label}</h3>
                                            <ul className="space-y-3">
                                                {group.items.map((booking) => (
                                                    <BookingRow
                                                        key={booking.id}
                                                        professionalId={professional?.id}
                                                        booking={booking}
                                                    />
                                                ))}
                                            </ul>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </section>
                    </div>
                )}
            </div>
        </HealthProfessionalLayout>
    );
}
