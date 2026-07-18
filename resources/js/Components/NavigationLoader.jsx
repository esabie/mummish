import { useEffect, useState } from 'react';

function subscribe(type, handler) {
    const event = `inertia:${type}`;
    document.addEventListener(event, handler);
    return () => document.removeEventListener(event, handler);
}

export default function NavigationLoader() {
    const [active, setActive] = useState(false);

    useEffect(() => {
        const show = () => setActive(true);
        const hide = () => setActive(false);

        const unsubStart = subscribe('start', show);
        const unsubFinish = subscribe('finish', hide);
        const unsubError = subscribe('error', hide);
        const unsubInvalid = subscribe('invalid', hide);

        return () => {
            unsubStart();
            unsubFinish();
            unsubError();
            unsubInvalid();
        };
    }, []);

    if (!active) {
        return null;
    }

    return (
        <div
            className="pointer-events-auto fixed inset-0 z-[100] flex items-center justify-center bg-stone-900/30 backdrop-blur-sm transition-opacity duration-200"
            aria-live="polite"
            aria-busy="true"
            role="status"
        >
            <div className="pointer-events-none flex flex-col items-center gap-4 rounded-2xl bg-white/95 px-10 py-8 shadow-2xl ring-2 ring-market/50">
                <div
                    className="h-14 w-14 animate-spin rounded-full border-4 border-market/25 border-t-market"
                    aria-hidden
                />
                <p className="text-sm font-semibold tracking-wide text-stone-700">Loading…</p>
            </div>
        </div>
    );
}
