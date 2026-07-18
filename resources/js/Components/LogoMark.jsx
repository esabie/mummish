import { Link } from '@inertiajs/react';

const LOGO_SRC = '/images/logo.png?v=3';

export function LogoIcon({ className = 'h-14 w-auto' }) {
    return (
        <img
            src={LOGO_SRC}
            alt=""
            className={`shrink-0 object-contain ${className}`}
            aria-hidden
        />
    );
}

export default function LogoMark({
    showText = false,
    variant = 'default',
    className = '',
    iconClassName,
    textClassName,
    href = '/',
}) {
    const textColorClass =
        variant === 'footer'
            ? 'text-footer-pink'
            : variant === 'shop'
              ? 'text-stone-900'
              : 'text-neutral-900';

    return (
        <Link href={href} className={`flex shrink-0 items-center gap-3 ${className}`}>
            <LogoIcon className={iconClassName ?? 'h-14 w-auto sm:h-16'} />
            {showText && (
                <span
                    className={`text-2xl font-bold tracking-tight sm:text-3xl ${textClassName ?? textColorClass}`}
                >
                    Mummish
                </span>
            )}
        </Link>
    );
}
