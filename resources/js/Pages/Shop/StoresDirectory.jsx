import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Breadcrumbs from '@/Components/Breadcrumbs';
import LogoMark from '@/Components/LogoMark';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';

function IconSearch(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    );
}

function buildPagination(current, last) {
    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }
    if (current <= 4) {
        return [1, 2, 3, 'ellipsis', last];
    }
    if (current >= last - 3) {
        return [1, 'ellipsis', last - 2, last - 1, last];
    }
    return [1, 'ellipsis', current - 1, current, current + 1, 'ellipsis', last];
}

function StoreCard({ store }) {
    return (
        <Link
            href={route('shops.show', store.slug)}
            className="group flex flex-col items-center rounded-2xl border border-stone-200/90 bg-white p-5 text-center shadow-sm transition hover:-translate-y-1 hover:border-market/40 hover:shadow-lg"
        >
            {store.image ? (
                <div className="h-20 w-20 overflow-hidden rounded-full bg-stone-100 ring-1 ring-stone-200/80 transition group-hover:ring-market/40 sm:h-24 sm:w-24">
                    <img
                        src={store.image}
                        alt=""
                        loading="lazy"
                        decoding="async"
                        className="h-full w-full object-cover"
                    />
                </div>
            ) : (
                <div className="flex h-20 w-20 items-center justify-center rounded-full bg-stone-200 text-2xl font-bold text-stone-600 ring-1 ring-stone-200/80 transition group-hover:ring-market/40 sm:h-24 sm:w-24">
                    {store.initial}
                </div>
            )}
            <p className="mt-3 line-clamp-2 text-sm font-semibold leading-snug text-stone-900">{store.name}</p>
            <p className="mt-1 text-xs text-stone-500">{store.category}</p>
            <p className="mt-0.5 text-xs text-stone-400">
                {store.product_count} {store.product_count === 1 ? 'product' : 'products'}
            </p>
        </Link>
    );
}

export default function StoresDirectory({ search_query, stores, result_count, current_page, last_page }) {
    const [searchInput, setSearchInput] = useState(search_query);

    useEffect(() => {
        setSearchInput(search_query);
    }, [search_query]);

    const submitSearch = (e) => {
        e.preventDefault();
        const q = searchInput.trim();
        router.get(route('shops.index'), { page: 1, ...(q ? { q } : {}) }, { preserveState: true });
    };

    const pageHref = (page) =>
        route('shops.index', { page, ...(search_query ? { q: search_query } : {}) });

    const pages = buildPagination(current_page, last_page);

    return (
        <>
            <SeoHead
                title="Stores"
                description="Browse trusted family stores on Mummish — local Ghanaian sellers for baby gear, clothing, toys, and more."
                url={route('shops.index')}
                image="/images/logo.png"
            />

            <div className="flex min-h-screen flex-col bg-[#faf9f7] text-stone-900 antialiased">
                <header className="border-b border-stone-200/90 bg-white/95 backdrop-blur">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
                        <LogoMark variant="shop" />
                        <Link
                            href={route('shop.index')}
                            className="rounded-full px-3 py-1.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-100"
                        >
                            Shop products
                        </Link>
                    </div>
                </header>

                <div className="border-b border-stone-200/90 bg-white/90">
                    <div className="mx-auto max-w-7xl px-4 py-2.5 sm:px-6 lg:px-8">
                        <Breadcrumbs
                            tone="shop"
                            items={[
                                { label: 'Home', href: '/' },
                                { label: 'Stores' },
                            ]}
                        />
                    </div>
                </div>

                <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div className="min-w-0">
                            <h1 className="text-xl font-bold tracking-tight text-stone-900 sm:text-3xl">
                                {search_query
                                    ? `${result_count} ${result_count === 1 ? 'store' : 'stores'} for '${search_query}'`
                                    : `${result_count} ${result_count === 1 ? 'store' : 'stores'}`}
                            </h1>
                            <p className="mt-1 text-sm text-stone-600 sm:text-base">
                                Browse every approved shop on Mummish.
                            </p>
                        </div>
                        <form onSubmit={submitSearch} className="w-full sm:max-w-xs">
                            <div className="relative">
                                <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-stone-400">
                                    <IconSearch className="h-5 w-5" />
                                </span>
                                <input
                                    type="search"
                                    value={searchInput}
                                    onChange={(e) => setSearchInput(e.target.value)}
                                    placeholder="Search stores…"
                                    className="w-full rounded-full border border-stone-200 bg-white py-2.5 pl-10 pr-4 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-market focus:outline-none focus:ring-1 focus:ring-market"
                                />
                            </div>
                        </form>
                    </div>

                    {stores.length === 0 ? (
                        <div className="mt-8 rounded-2xl border border-dashed border-stone-300 bg-white/70 px-6 py-16 text-center">
                            <p className="text-lg font-semibold text-stone-800">No stores found</p>
                            <p className="mt-2 text-sm text-stone-500">
                                {search_query
                                    ? `No stores match "${search_query}".`
                                    : 'No stores have been approved yet — check back soon.'}
                            </p>
                        </div>
                    ) : (
                        <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6 lg:grid-cols-4 xl:grid-cols-6">
                            {stores.map((store) => (
                                <StoreCard key={store.slug} store={store} />
                            ))}
                        </div>
                    )}

                    {last_page > 1 && (
                        <nav
                            className="mt-12 flex flex-wrap items-center justify-center gap-2"
                            aria-label="Pagination"
                        >
                            {current_page <= 1 ? (
                                <span
                                    className="flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-full border border-stone-200 bg-white text-stone-400 opacity-50 shadow-sm"
                                    aria-label="Previous page"
                                    aria-disabled
                                >
                                    <span aria-hidden>←</span>
                                </span>
                            ) : (
                                <Link
                                    href={pageHref(current_page - 1)}
                                    className="flex h-10 w-10 items-center justify-center rounded-full border border-stone-200 bg-white text-stone-700 shadow-sm hover:border-market hover:text-market"
                                    aria-label="Previous page"
                                >
                                    <span aria-hidden>←</span>
                                </Link>
                            )}
                            {pages.map((p, i) =>
                                p === 'ellipsis' ? (
                                    <span key={`e-${i}`} className="px-2 text-stone-400">
                                        …
                                    </span>
                                ) : (
                                    <Link
                                        key={p}
                                        href={pageHref(p)}
                                        className={`flex h-10 min-w-[2.5rem] items-center justify-center rounded-full px-3 text-sm font-semibold ${
                                            p === current_page
                                                ? 'bg-market text-white shadow-sm'
                                                : 'border border-stone-200 bg-white text-stone-700 hover:border-market hover:text-market'
                                        }`}
                                    >
                                        {p}
                                    </Link>
                                )
                            )}
                            {current_page >= last_page ? (
                                <span
                                    className="flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-full border border-stone-200 bg-white text-stone-400 opacity-50 shadow-sm"
                                    aria-label="Next page"
                                    aria-disabled
                                >
                                    <span aria-hidden>→</span>
                                </span>
                            ) : (
                                <Link
                                    href={pageHref(current_page + 1)}
                                    className="flex h-10 w-10 items-center justify-center rounded-full border border-stone-200 bg-white text-stone-700 shadow-sm hover:border-market hover:text-market"
                                    aria-label="Next page"
                                >
                                    <span aria-hidden>→</span>
                                </Link>
                            )}
                        </nav>
                    )}
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
