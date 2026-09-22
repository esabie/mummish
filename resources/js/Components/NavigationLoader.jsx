import { useEffect, useState } from 'react';
import { LogoIcon } from '@/Components/LogoMark';

const SHOW_DELAY_MS = 0;
const MIN_VISIBLE_MS = 700;

function subscribe(type, handler) {
    const event = `inertia:${type}`;
    document.addEventListener(event, handler);
    return () => document.removeEventListener(event, handler);
}

function wantsPreview() {
    if (typeof window === 'undefined') {
        return false;
    }
    return new URLSearchParams(window.location.search).has('previewLoader');
}

export default function NavigationLoader() {
    const [active, setActive] = useState(() => wantsPreview());

    useEffect(() => {
        if (wantsPreview()) {
            setActive(true);
            return undefined;
        }

        let showTimer = null;
        let hideTimer = null;
        let shownAt = 0;

        const clearTimers = () => {
            if (showTimer) {
                window.clearTimeout(showTimer);
                showTimer = null;
            }
            if (hideTimer) {
                window.clearTimeout(hideTimer);
                hideTimer = null;
            }
        };

        const show = () => {
            clearTimers();
            showTimer = window.setTimeout(() => {
                shownAt = Date.now();
                setActive(true);
            }, SHOW_DELAY_MS);
        };

        const hide = () => {
            if (showTimer) {
                window.clearTimeout(showTimer);
                showTimer = null;
            }

            const elapsed = shownAt ? Date.now() - shownAt : 0;
            const remaining = Math.max(0, MIN_VISIBLE_MS - elapsed);

            hideTimer = window.setTimeout(() => {
                setActive(false);
                shownAt = 0;
            }, remaining);
        };

        const unsubStart = subscribe('start', show);
        const unsubFinish = subscribe('finish', hide);
        const unsubError = subscribe('error', hide);
        const unsubInvalid = subscribe('invalid', hide);

        return () => {
            clearTimers();
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
            className="pointer-events-auto fixed inset-0 z-[100] flex items-center justify-center bg-stone-900/25 backdrop-blur-[2px] transition-opacity duration-200"
            aria-live="polite"
            aria-busy="true"
            role="status"
        >
            <div className="pointer-events-none flex items-center justify-center rounded-3xl bg-white/95 px-12 py-10 shadow-2xl ring-1 ring-market/20">
                <div className="relative flex h-28 w-44 items-center justify-center">
                    <span
                        className="absolute inset-0 m-auto h-24 w-24 rounded-full bg-market/15 mummish-loader-halo"
                        aria-hidden
                    />
                    <span
                        className="absolute inset-0 m-auto h-[6.5rem] w-[6.5rem] rounded-full border-2 border-transparent border-t-market border-r-market/40 mummish-loader-orbit"
                        aria-hidden
                    />
                    <LogoIcon className="relative z-10 h-12 w-auto mummish-loader-logo sm:h-14" />
                </div>
            </div>
        </div>
    );
}
