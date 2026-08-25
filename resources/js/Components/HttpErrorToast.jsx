import { useEffect, useState } from 'react';
import { subscribeHttpErrors } from '@/utils/httpErrorBus';

const AUTO_DISMISS_MS = 8000;

export default function HttpErrorToast() {
    const [toast, setToast] = useState(null);

    useEffect(() => {
        return subscribeHttpErrors((detail) => {
            setToast(detail);
        });
    }, []);

    useEffect(() => {
        if (!toast) {
            return undefined;
        }

        const timer = window.setTimeout(() => setToast(null), AUTO_DISMISS_MS);

        return () => window.clearTimeout(timer);
    }, [toast]);

    if (!toast) {
        return null;
    }

    return (
        <div
            className="pointer-events-none fixed inset-x-0 top-0 z-[100] flex justify-center px-4 pt-4 sm:px-6"
            role="alert"
            aria-live="assertive"
        >
            <div className="pointer-events-auto flex w-full max-w-lg items-start gap-3 rounded-xl border border-red-200 bg-white px-4 py-3 shadow-lg ring-1 ring-red-100">
                <div className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-700">
                    <svg viewBox="0 0 20 20" fill="currentColor" className="h-5 w-5" aria-hidden>
                        <path
                            fillRule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z"
                            clipRule="evenodd"
                        />
                    </svg>
                </div>
                <div className="min-w-0 flex-1">
                    <p className="text-sm font-semibold text-stone-900">Upload or request failed</p>
                    <p className="mt-0.5 text-sm leading-relaxed text-stone-600">{toast.message}</p>
                </div>
                <button
                    type="button"
                    onClick={() => setToast(null)}
                    className="rounded-md p-1 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700"
                    aria-label="Dismiss"
                >
                    <svg viewBox="0 0 20 20" fill="currentColor" className="h-5 w-5" aria-hidden>
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                </button>
            </div>
        </div>
    );
}
