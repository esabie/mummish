import { useState } from 'react';
import { router } from '@inertiajs/react';
import Modal from '@/Components/Modal';

export function BookingStatusChip({ status }) {
    const styles = {
        pending: 'bg-amber-100 text-amber-900',
        confirmed: 'bg-emerald-100 text-emerald-800',
        cancelled: 'bg-stone-200 text-stone-600',
        completed: 'bg-sky-100 text-sky-800',
    };

    const labels = {
        pending: 'Pending',
        confirmed: 'Confirmed',
        cancelled: 'Cancelled',
        completed: 'Completed',
    };

    return (
        <span
            className={`rounded-full px-2 py-0.5 text-[11px] font-semibold ${styles[status] ?? 'bg-stone-100 text-stone-600'}`}
        >
            {labels[status] ?? status}
        </span>
    );
}

export function BookingActionButtons({ professionalId, booking, compact = false }) {
    const [reasonModal, setReasonModal] = useState(null);
    const [reason, setReason] = useState('');
    const [processing, setProcessing] = useState(false);

    if (!professionalId || !booking) {
        return null;
    }

    const postAction = (routeName, data = {}) => {
        setProcessing(true);
        router.post(route(routeName, [professionalId, booking.id]), data, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setReasonModal(null);
                setReason('');
            },
        });
    };

    const btnBase = compact
        ? 'rounded-lg px-2.5 py-1.5 text-[11px] font-semibold transition'
        : 'rounded-lg px-3 py-2 text-xs font-semibold transition';

    return (
        <>
            <div className="flex flex-wrap gap-2">
                {booking.can_confirm ? (
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => postAction('health-professionals.bookings.confirm')}
                        className={`${btnBase} bg-[#5c4d3d] text-white hover:bg-[#4a3e32] disabled:opacity-50`}
                    >
                        Confirm
                    </button>
                ) : null}
                {booking.can_decline ? (
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => setReasonModal('decline')}
                        className={`${btnBase} border border-stone-300 bg-white text-stone-700 hover:border-stone-400 disabled:opacity-50`}
                    >
                        Decline
                    </button>
                ) : null}
                {booking.can_complete ? (
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => postAction('health-professionals.bookings.complete')}
                        className={`${btnBase} bg-emerald-700 text-white hover:bg-emerald-800 disabled:opacity-50`}
                    >
                        Mark completed
                    </button>
                ) : null}
                {booking.can_cancel ? (
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => setReasonModal('cancel')}
                        className={`${btnBase} border border-red-200 bg-white text-red-700 hover:bg-red-50 disabled:opacity-50`}
                    >
                        Cancel
                    </button>
                ) : null}
            </div>

            <Modal
                show={Boolean(reasonModal)}
                onClose={() => {
                    if (!processing) {
                        setReasonModal(null);
                        setReason('');
                    }
                }}
                maxWidth="md"
                closeable={!processing}
            >
                <div className="px-6 py-6">
                    <h2 className="text-lg font-bold text-stone-900">
                        {reasonModal === 'decline' ? 'Decline booking request' : 'Cancel booking'}
                    </h2>
                    <p className="mt-1 text-sm text-stone-600">
                        {booking.patient_name} · {booking.appointment_date_label} at {booking.appointment_time}
                    </p>
                    <label htmlFor="cancellation_reason" className="mt-4 block text-sm font-medium text-stone-700">
                        Reason (optional)
                    </label>
                    <textarea
                        id="cancellation_reason"
                        rows={3}
                        value={reason}
                        onChange={(e) => setReason(e.target.value)}
                        className="mt-1 block w-full rounded-lg border border-stone-200 px-3 py-2 text-sm shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                        placeholder="Shown to the patient in their SMS notification"
                        disabled={processing}
                    />
                    <div className="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            disabled={processing}
                            onClick={() => {
                                setReasonModal(null);
                                setReason('');
                            }}
                            className="rounded-lg border border-stone-200 px-3 py-2 text-xs font-semibold text-stone-700 hover:bg-stone-50"
                        >
                            Keep booking
                        </button>
                        <button
                            type="button"
                            disabled={processing}
                            onClick={() =>
                                postAction(
                                    reasonModal === 'decline'
                                        ? 'health-professionals.bookings.decline'
                                        : 'health-professionals.bookings.cancel',
                                    { cancellation_reason: reason },
                                )
                            }
                            className="rounded-lg bg-red-700 px-3 py-2 text-xs font-semibold text-white hover:bg-red-800 disabled:opacity-50"
                        >
                            {processing
                                ? 'Sending…'
                                : reasonModal === 'decline'
                                  ? 'Decline & notify'
                                  : 'Cancel & notify'}
                        </button>
                    </div>
                </div>
            </Modal>
        </>
    );
}

export function BookingRow({ professionalId, booking }) {
    return (
        <li className="rounded-xl border border-stone-200 bg-white px-4 py-3">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="text-sm font-semibold text-stone-900">{booking.patient_name}</p>
                        <BookingStatusChip status={booking.status} />
                    </div>
                    <p className="mt-1 text-sm text-stone-700">
                        {booking.appointment_date_label} · {booking.appointment_time}
                        {booking.visit_mode ? ` · ${booking.visit_mode}` : ''}
                    </p>
                    <p className="mt-0.5 text-xs text-stone-500">
                        {booking.reference}
                        {booking.service_name ? ` · ${booking.service_name}` : ''}
                        {booking.patient_phone ? ` · ${booking.patient_phone}` : ''}
                    </p>
                    {booking.notes ? (
                        <p className="mt-2 text-xs text-stone-600">Note: {booking.notes}</p>
                    ) : null}
                    {booking.cancellation_reason ? (
                        <p className="mt-1 text-xs text-stone-500">Cancel reason: {booking.cancellation_reason}</p>
                    ) : null}
                </div>
                <BookingActionButtons professionalId={professionalId} booking={booking} compact />
            </div>
        </li>
    );
}
