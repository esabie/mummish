import { Link } from '@inertiajs/react';

const tones = {
    default: {
        sep: 'text-gray-400',
        link: 'text-gray-600 underline-offset-2 transition hover:text-gray-900 hover:underline',
        current: 'font-semibold text-gray-900',
        midText: 'text-gray-600',
    },
    shop: {
        sep: 'text-stone-400',
        link: 'text-stone-600 underline-offset-2 transition hover:text-stone-900 hover:underline',
        current: 'font-semibold text-stone-900',
        midText: 'text-stone-600',
    },
};

/**
 * @param {{ label: string, href?: string }[]} items
 * Last item is treated as the current page (no link). Earlier items with `href` render as links.
 * @param {'default' | 'shop'} [tone]
 */
export default function Breadcrumbs({ items = [], className = '', tone = 'default' }) {
    if (!items.length) {
        return null;
    }

    const t = tones[tone] ?? tones.default;

    return (
        <nav aria-label="Breadcrumb" className={className}>
            <ol className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                {items.map((item, i) => {
                    const isLast = i === items.length - 1;
                    return (
                        <li key={`${item.label}-${i}`} className="flex items-center gap-2">
                            {i > 0 && (
                                <span className={`select-none ${t.sep}`} aria-hidden="true">
                                    /
                                </span>
                            )}
                            {!isLast && item.href ? (
                                <Link href={item.href} className={t.link}>
                                    {item.label}
                                </Link>
                            ) : (
                                <span
                                    className={`${isLast ? t.current : t.midText} ${isLast ? 'max-w-[min(100%,18rem)] truncate sm:max-w-md' : ''}`}
                                    title={isLast ? item.label : undefined}
                                    aria-current={isLast ? 'page' : undefined}
                                >
                                    {item.label}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
