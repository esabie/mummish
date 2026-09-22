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
            className="pointer-events-auto fixed inset-0 z-[100] flex items-center justify-center bg-stone-900/30 px-4 py-6 backdrop-blur-[1px] sm:bg-stone-900/25 sm:backdrop-blur-[2px] supports-[padding:max(0px)]:pt-[max(1.5rem,env(safe-area-inset-top))] supports-[padding:max(0px)]:pb-[max(1.5rem,env(safe-area-inset-bottom))]"
            aria-live="polite"
            aria-busy="true"
            role="status"
        >
            <div className="pointer-events-none flex w-full max-w-[17.5rem] items-center justify-center overflow-visible rounded-2xl bg-white/95 px-6 py-8 shadow-2xl ring-1 ring-market/20 sm:max-w-none sm:rounded-3xl sm:px-12 sm:py-10">
                <div className="relative flex h-24 w-full max-w-[14rem] items-center justify-center sm:h-28 sm:w-52 sm:max-w-none">
                    {/* Soft breathing halo — oval to match the wordmark */}
                    <span
                        className="absolute inset-0 m-auto h-16 w-36 rounded-full bg-market/15 mummish-loader-halo sm:h-20 sm:w-44"
                        aria-hidden
                    />
                    {/* Orbiting ring — oval so it frames the logo on narrow screens */}
                    <span
                        className="absolute inset-0 m-auto h-[4.75rem] w-[11.5rem] rounded-full border-2 border-transparent border-t-market border-r-market/40 mummish-loader-orbit sm:h-[6.25rem] sm:w-[13.5rem]"
                        aria-hidden
                    />
                    <LogoIcon className="relative z-10 h-10 w-auto max-w-[11rem] mummish-loader-logo sm:h-14 sm:max-w-none" />
                </div>
            </div>
        </div>
    );
}
