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

export function BookingActionButtons({ professionalId, booking, compact = false, tone = 'default' }) {
    const [reasonModal, setReasonModal] = useState(null);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [meetLinkOpen, setMeetLinkOpen] = useState(false);
    const [reason, setReason] = useState('');
    const [meetingUrl, setMeetingUrl] = useState('');
    const [meetingLocation, setMeetingLocation] = useState('');
    const [meetingWhatsapp, setMeetingWhatsapp] = useState('');
    const [logisticsNotes, setLogisticsNotes] = useState('');
    const [processing, setProcessing] = useState(false);

    if (!professionalId || !booking) {
        return null;
    }

    const isInPerson = String(booking.visit_mode || '').toLowerCase() === 'in person';
    const isVirtual = String(booking.visit_mode || '').toLowerCase() === 'virtual';
    const googleMeetUrl = booking.meeting_url || null;

    const postAction = (routeName, data = {}, onDone = null) => {
        setProcessing(true);
        router.post(route(routeName, [professionalId, booking.id]), data, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setReasonModal(null);
                setReason('');
                onDone?.();
            },
        });
    };

    const submitConfirm = () => {
        if (isInPerson && !meetingLocation.trim()) {
            return;
        }
        if (isVirtual && !meetingUrl.trim()) {
            return;
        }

        postAction(
            'health-professionals.bookings.confirm',
            {
                meeting_url: meetingUrl,
                meeting_location: meetingLocation,
                meeting_whatsapp: meetingWhatsapp,
                logistics_notes: logisticsNotes,
            },
            () => {
                setConfirmOpen(false);
                setMeetingUrl('');
                setMeetingLocation('');
                setMeetingWhatsapp('');
                setLogisticsNotes('');
            },
        );
    };

    const submitMeetLink = () => {
        if (!meetingUrl.trim()) {
            return;
        }

        postAction(
            'health-professionals.bookings.meeting-url',
            { meeting_url: meetingUrl },
            () => {
                setMeetLinkOpen(false);
                setMeetingUrl('');
            },
        );
    };

    const onDark = tone === 'onDark';
    const btnBase = compact
        ? 'w-full rounded-lg px-2.5 py-2 text-[11px] font-semibold transition sm:w-auto sm:py-1.5'
        : 'w-full rounded-lg px-3 py-2.5 text-xs font-semibold transition sm:w-auto sm:py-2';

    const confirmClass = onDark
        ? 'bg-white text-[#5c4d3d] hover:bg-stone-100 disabled:opacity-50'
        : 'bg-[#5c4d3d] text-white hover:bg-[#4a3e32] disabled:opacity-50';

    const secondaryClass = onDark
        ? 'border border-white/40 bg-transparent text-white hover:bg-white/10 disabled:opacity-50'
        : 'border border-stone-300 bg-white text-stone-700 hover:border-stone-400 disabled:opacity-50';

    const completeClass = onDark
        ? 'bg-emerald-400 text-emerald-950 hover:bg-emerald-300 disabled:opacity-50'
        : 'bg-emerald-700 text-white hover:bg-emerald-800 disabled:opacity-50';

    const cancelClass = onDark
        ? 'border border-red-300/70 bg-transparent text-red-100 hover:bg-red-500/20 disabled:opacity-50'
        : 'border border-red-200 bg-white text-red-700 hover:bg-red-50 disabled:opacity-50';

    return (
        <>
            <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap">
                {booking.can_confirm ? (
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => setConfirmOpen(true)}
                        className={`${btnBase} ${confirmClass}`}
                    >
                        Confirm
                    </button>
                ) : null}
                {booking.can_join_session && googleMeetUrl ? (
                    <a
                        href={googleMeetUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={`${btnBase} ${completeClass} text-center`}
                    >
                        Start Session
                    </a>
                ) : null}
                {booking.can_add_meeting_url ? (
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => {
                            setMeetingUrl('');
                            setMeetLinkOpen(true);
                        }}
                        className={`${btnBase} ${completeClass}`}
                    >
                        Add Google Meet link
                    </button>
                ) : null}
                {booking.can_decline ? (
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => setReasonModal('decline')}
                        className={`${btnBase} ${secondaryClass}`}
                    >
                        Decline
                    </button>
                ) : null}
                {booking.can_complete ? (
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => postAction('health-professionals.bookings.complete')}
                        className={`${btnBase} ${completeClass}`}
                    >
                        Mark completed
                    </button>
                ) : null}
                {booking.can_cancel ? (
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => setReasonModal('cancel')}
                        className={`${btnBase} ${cancelClass}`}
                    >
                        Cancel
                    </button>
                ) : null}
            </div>

            <Modal
                show={confirmOpen}
                onClose={() => {
                    if (!processing) {
                        setConfirmOpen(false);
                    }
                }}
                maxWidth="md"
                closeable={!processing}
            >
                <div className="px-4 py-5 sm:px-6 sm:py-6">
                    <h2 className="text-lg font-bold text-stone-900">Confirm booking</h2>
                    <p className="mt-1 text-sm text-stone-600">
                        {booking.patient_name} · {booking.appointment_date_label} at {booking.appointment_time}
                        {booking.visit_mode ? ` · ${booking.visit_mode}` : ''}
                    </p>

                    {isVirtual ? (
                        <div className="mt-4 space-y-3">
                            <p className="rounded-lg bg-sky-50 px-3 py-2 text-sm text-sky-900">
                                Create a Google Meet link (Meet → New meeting → Create meeting for later), then paste it
                                below. We will SMS it to the patient.
                            </p>
                            <div>
                                <label htmlFor="meeting_url" className="block text-sm font-medium text-stone-700">
                                    Google Meet link
                                </label>
                                <input
                                    id="meeting_url"
                                    type="url"
                                    value={meetingUrl}
                                    onChange={(e) => setMeetingUrl(e.target.value)}
                                    className="mt-1 block w-full rounded-lg border border-stone-200 px-3 py-2 text-sm shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                                    placeholder="https://meet.google.com/abc-defg-hij"
                                    disabled={processing}
                                    required
                                />
                            </div>
                        </div>
                    ) : null}

                    {isInPerson ? (
                        <div className="mt-4">
                            <label htmlFor="meeting_location" className="block text-sm font-medium text-stone-700">
                                Meeting location
                            </label>
                            <textarea
                                id="meeting_location"
                                rows={2}
                                value={meetingLocation}
                                onChange={(e) => setMeetingLocation(e.target.value)}
                                className="mt-1 block w-full rounded-lg border border-stone-200 px-3 py-2 text-sm shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                                placeholder="Clinic address or directions for the patient"
                                disabled={processing}
                                required
                            />
                        </div>
                    ) : null}

                    <div className="mt-4">
                        <label htmlFor="meeting_whatsapp" className="block text-sm font-medium text-stone-700">
                            WhatsApp contact (optional)
                        </label>
                        <input
                            id="meeting_whatsapp"
                            type="tel"
                            value={meetingWhatsapp}
                            onChange={(e) => setMeetingWhatsapp(e.target.value)}
                            className="mt-1 block w-full rounded-lg border border-stone-200 px-3 py-2 text-sm shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                            placeholder="024…"
                            disabled={processing}
                        />
                    </div>

                    <div className="mt-4">
                        <label htmlFor="logistics_notes" className="block text-sm font-medium text-stone-700">
                            Extra notes (optional)
                        </label>
                        <textarea
                            id="logistics_notes"
                            rows={2}
                            value={logisticsNotes}
                            onChange={(e) => setLogisticsNotes(e.target.value)}
                            className="mt-1 block w-full rounded-lg border border-stone-200 px-3 py-2 text-sm shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                            placeholder="Anything else the patient should know"
                            disabled={processing}
                        />
                    </div>

                    <div className="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            disabled={processing}
                            onClick={() => setConfirmOpen(false)}
                            className="rounded-lg border border-stone-200 px-3 py-2.5 text-xs font-semibold text-stone-700 hover:bg-stone-50 sm:py-2"
                        >
                            Keep pending
                        </button>
                        <button
                            type="button"
                            disabled={
                                processing ||
                                (isInPerson && !meetingLocation.trim()) ||
                                (isVirtual && !meetingUrl.trim())
                            }
                            onClick={submitConfirm}
                            className="rounded-lg bg-[#5c4d3d] px-3 py-2.5 text-xs font-semibold text-white hover:bg-[#4a3e32] disabled:opacity-50 sm:py-2"
                        >
                            {processing ? 'Confirming…' : 'Confirm & notify'}
                        </button>
                    </div>
                </div>
            </Modal>

            <Modal
                show={meetLinkOpen}
                onClose={() => {
                    if (!processing) {
                        setMeetLinkOpen(false);
                    }
                }}
                maxWidth="md"
                closeable={!processing}
            >
                <div className="px-4 py-5 sm:px-6 sm:py-6">
                    <h2 className="text-lg font-bold text-stone-900">Add Google Meet link</h2>
                    <p className="mt-1 text-sm text-stone-600">
                        {booking.patient_name} · {booking.reference}
                        {booking.visit_mode ? ` · ${booking.visit_mode}` : ''}
                    </p>
                    <p className="mt-3 rounded-lg bg-sky-50 px-3 py-2 text-sm text-sky-900">
                        Create a Meet link (Meet → New meeting → Create meeting for later), paste it here, then share it
                        with the patient if needed.
                    </p>
                    <div className="mt-4">
                        <label htmlFor="add_meeting_url" className="block text-sm font-medium text-stone-700">
                            Google Meet link
                        </label>
                        <input
                            id="add_meeting_url"
                            type="url"
                            value={meetingUrl}
                            onChange={(e) => setMeetingUrl(e.target.value)}
                            className="mt-1 block w-full rounded-lg border border-stone-200 px-3 py-2 text-sm shadow-sm focus:border-[#5c4d3d] focus:outline-none focus:ring-1 focus:ring-[#5c4d3d]"
                            placeholder="https://meet.google.com/abc-defg-hij"
                            disabled={processing}
                            required
                        />
                    </div>
                    <div className="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            disabled={processing}
                            onClick={() => setMeetLinkOpen(false)}
                            className="rounded-lg border border-stone-200 px-3 py-2.5 text-xs font-semibold text-stone-700 hover:bg-stone-50 sm:py-2"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            disabled={processing || !meetingUrl.trim()}
                            onClick={submitMeetLink}
                            className="rounded-lg bg-[#5c4d3d] px-3 py-2.5 text-xs font-semibold text-white hover:bg-[#4a3e32] disabled:opacity-50 sm:py-2"
                        >
                            {processing ? 'Saving…' : 'Save Meet link'}
                        </button>
                    </div>
                </div>
            </Modal>

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
                <div className="px-4 py-5 sm:px-6 sm:py-6">
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
                    <div className="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            disabled={processing}
                            onClick={() => {
                                setReasonModal(null);
                                setReason('');
                            }}
                            className="rounded-lg border border-stone-200 px-3 py-2.5 text-xs font-semibold text-stone-700 hover:bg-stone-50 sm:py-2"
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
                            className="rounded-lg bg-red-700 px-3 py-2.5 text-xs font-semibold text-white hover:bg-red-800 disabled:opacity-50 sm:py-2"
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
        <li className="rounded-xl border border-stone-200 bg-white px-3 py-3 sm:px-4">
            <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="text-sm font-semibold text-stone-900">{booking.patient_name}</p>
                        <BookingStatusChip status={booking.status} />
                    </div>
                    <p className="mt-1 text-sm text-stone-700">
                        {booking.appointment_date_label} · {booking.appointment_time}
                        {booking.visit_mode ? ` · ${booking.visit_mode}` : ''}
                    </p>
                    <p className="mt-0.5 break-words text-xs text-stone-500">
                        {booking.reference}
                        {booking.service_name ? ` · ${booking.service_name}` : ''}
                        {booking.patient_phone ? ` · ${booking.patient_phone}` : ''}
                    </p>
                    {booking.meeting_location ? (
                        <p className="mt-2 text-xs text-stone-600">Location: {booking.meeting_location}</p>
                    ) : null}
                    {booking.meeting_url ? (
                        <p className="mt-2 break-all text-xs text-stone-600">Meet: {booking.meeting_url}</p>
                    ) : null}
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
