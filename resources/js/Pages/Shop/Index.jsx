import { Link, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useMemo, useRef } from 'react';
import Breadcrumbs from '@/Components/Breadcrumbs';
import LogoMark from '@/Components/LogoMark';
import ProductPrice from '@/Components/ProductPrice';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';
import { useCart } from '@/context/CartContext';

function IconSearch(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    );
}

function IconHeart(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
        </svg>
    );
}

function IconCart(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.5a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
        </svg>
    );
}

function IconUser(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
        </svg>
    );
}

function IconSliders(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
        </svg>
    );
}

function IconX(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    );
}

function isSoldOut(product) {
    return Boolean(product.sold_out) || (product.stock_quantity ?? 0) < 1;
}

function badgeClasses(variant) {
    switch (variant) {
        case 'eco':
            return 'bg-teal-100 text-teal-900 ring-1 ring-teal-200/80';
        case 'accent':
            return 'bg-amber-100 text-amber-950 ring-1 ring-amber-200/80';
        case 'bestseller':
            return 'bg-emerald-100 text-emerald-900 ring-1 ring-emerald-200/80';
        case 'new':
            return 'bg-rose-100 text-rose-900 ring-1 ring-rose-200/80';
        default:
            return 'bg-stone-200 text-stone-800 ring-1 ring-stone-300/80';
    }
}

const navItems = [
    { label: 'Shop', href: route('shop.index'), active: true },
    { label: 'About', href: route('about') },
    { label: 'Sell', href: route('vendor.signup') },
    { label: 'Gifts', href: '#' },
    { label: 'New Arrivals', href: '#' },
];

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

function buildShopParams({ search_query, applied_filters, filter_options, page = 1, overrides = {} }) {
    const filters = { ...applied_filters, ...overrides };
    const params = { page };

    if (search_query) {
        params.q = search_query;
    }
    if (filters.category) {
        params.category = filters.category;
    }
    if (filters.price_max && filters.price_max < filter_options.price_ceiling) {
        params.price_max = filters.price_max;
    }
    if (filters.eco) {
        params.eco = 1;
    }
    if (filters.maker) {
        params.maker = filters.maker;
    }
    if (filters.condition) {
        params.condition = filters.condition;
    }
    if (filters.sort && filters.sort !== 'newest') {
        params.sort = filters.sort;
    }

    return params;
}

function drawerFilterCount(applied_filters, filter_options) {
    let count = 0;
    if (applied_filters.price_max && applied_filters.price_max < filter_options.price_ceiling) {
        count += 1;
    }
    if (applied_filters.eco) {
        count += 1;
    }
    if (applied_filters.maker) {
        count += 1;
    }
    return count;
}

export default function ShopIndex({
    search_query,
    result_count,
    current_page,
    last_page,
    items,
    filter_options = { categories: [], conditions: [], makers: [], price_ceiling: 200 },
    applied_filters = { category: null, price_max: null, eco: false, maker: null, condition: null, sort: 'newest' },
}) {
    const { flash } = usePage().props;
    const { openCart, addItem, getRemainingStock, count: cartCount } = useCart();
    const [searchInput, setSearchInput] = useState(search_query);
    const [filtersOpen, setFiltersOpen] = useState(false);
    const [draftPriceMax, setDraftPriceMax] = useState(filter_options.price_ceiling);
    const [draftEco, setDraftEco] = useState(false);
    const [draftMaker, setDraftMaker] = useState(null);
    const [draftCondition, setDraftCondition] = useState(null);

    // Items from earlier pages accumulated by mobile "Load more".
    const [previousItems, setPreviousItems] = useState([]);
    const [loadingMore, setLoadingMore] = useState(false);
    const loadMoreSentinelRef = useRef(null);

    const filtersKey = JSON.stringify(applied_filters);

    useEffect(() => {
        setSearchInput(search_query);
    }, [search_query]);

    // New search/filter/sort means a fresh result set: drop accumulated pages.
    useEffect(() => {
        setPreviousItems([]);
    }, [search_query, filtersKey]);

    const displayItems = useMemo(() => {
        const seen = new Set();
        return [...previousItems, ...items].filter((p) => {
            if (seen.has(p.id)) {
                return false;
            }
            seen.add(p.id);
            return true;
        });
    }, [previousItems, items]);

    const hasMore = current_page < last_page;

    const loadMore = () => {
        if (loadingMore || !hasMore) {
            return;
        }
        setLoadingMore(true);
        const currentItems = items;
        router.get(
            route('shop.index'),
            buildShopParams({ search_query, applied_filters, filter_options, page: current_page + 1 }),
            {
                preserveState: true,
                preserveScroll: true,
                only: ['items', 'current_page', 'last_page', 'result_count'],
                onSuccess: () => setPreviousItems((prev) => [...prev, ...currentItems]),
                onFinish: () => setLoadingMore(false),
            }
        );
    };

    // Auto-load next page when the mobile sentinel scrolls into view.
    // The sentinel is display:none from sm up, so it never intersects on desktop.
    useEffect(() => {
        const sentinel = loadMoreSentinelRef.current;
        if (!sentinel || !hasMore) {
            return;
        }
        const observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    loadMore();
                }
            },
            { rootMargin: '400px 0px' }
        );
        observer.observe(sentinel);
        return () => observer.disconnect();
    });

    useEffect(() => {
        if (!filtersOpen) {
            return;
        }
        setDraftPriceMax(applied_filters.price_max ?? filter_options.price_ceiling);
        setDraftEco(applied_filters.eco);
        setDraftMaker(applied_filters.maker);
        setDraftCondition(applied_filters.condition);
    }, [filtersOpen, applied_filters, filter_options.price_ceiling]);

    const applyShopFilters = (overrides = {}, options = {}) => {
        router.get(
            route('shop.index'),
            buildShopParams({ search_query, applied_filters, filter_options, page: 1, overrides }),
            { preserveState: true, preserveScroll: true, ...options }
        );
    };

    const submitSearch = (e) => {
        e.preventDefault();
        const q = searchInput.trim();
        router.get(route('shop.index'), { page: 1, ...(q ? { q } : {}) }, { preserveState: true });
    };

    const pageHref = (page) =>
        route(
            'shop.index',
            buildShopParams({ search_query, applied_filters, filter_options, page })
        );

    const pages = buildPagination(current_page, last_page);
    const activeDrawerFilters = drawerFilterCount(applied_filters, filter_options);

    const toggleCategory = (categoryId) => {
        applyShopFilters({
            category: applied_filters.category === categoryId ? null : categoryId,
        });
    };

    const toggleCondition = (conditionId) => {
        applyShopFilters({
            condition: applied_filters.condition === conditionId ? null : conditionId,
        });
    };

    const applyDrawerFilters = () => {
        applyShopFilters({
            price_max: draftPriceMax < filter_options.price_ceiling ? draftPriceMax : null,
            eco: draftEco,
            maker: draftMaker,
            condition: draftCondition,
        });
        setFiltersOpen(false);
    };

    const clearDrawerFilters = () => {
        setDraftPriceMax(filter_options.price_ceiling);
        setDraftEco(false);
        setDraftMaker(null);
        setDraftCondition(null);
    };

    const clearAllFilters = () => {
        setFiltersOpen(false);
        router.get(
            route('shop.index'),
            { page: 1, ...(search_query ? { q: search_query } : {}) },
            { preserveState: true, preserveScroll: true }
        );
    };

    const hasActiveFilters =
        applied_filters.category ||
        applied_filters.condition ||
        activeDrawerFilters > 0 ||
        (applied_filters.sort && applied_filters.sort !== 'newest');

    // Only surface result counts once the catalog has been narrowed by a
    // search or a filter that actually restricts results (sort doesn't).
    const showResultCount = Boolean(
        search_query ||
            applied_filters.category ||
            applied_filters.condition ||
            activeDrawerFilters > 0
    );

    const seoTitle = search_query
        ? `Search: ${search_query}`
        : applied_filters.category
          ? filter_options.categories.find((c) => c.id === applied_filters.category)?.label || 'Shop'
          : 'Shop';

    const seoDescription = search_query
        ? `Browse ${result_count} results for “${search_query}” on Mummish, Ghana's marketplace for families.`
        : 'Shop baby and family essentials on Mummish — nursing, feeding, clothing, toys, and more from trusted Ghanaian sellers.';

    return (
        <>
            <SeoHead
                title={seoTitle}
                description={seoDescription}
                url={route('shop.index')}
                image="/images/logo.png"
            />

            <div className="flex min-h-screen flex-col bg-[#faf9f7] text-stone-900 antialiased">
                <header className="border-b border-stone-200/90 bg-white/95 backdrop-blur">
                    <div className="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <LogoMark variant="shop" />

                            <nav className="hidden items-center gap-8 md:flex">
                                {navItems.map((n) => (
                                    <a
                                        key={n.label}
                                        href={n.href}
                                        className={`text-sm font-medium transition ${
                                            n.active ? 'text-market' : 'text-stone-600 hover:text-stone-900'
                                        }`}
                                    >
                                        {n.label}
                                    </a>
                                ))}
                            </nav>

                            <form
                                onSubmit={submitSearch}
                                className="flex w-full flex-1 items-center gap-3 lg:max-w-md lg:justify-end"
                            >
                                <div className="relative min-w-0 flex-1">
                                    <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-stone-400">
                                        <IconSearch className="h-5 w-5" />
                                    </span>
                                    <input
                                        type="search"
                                        name="q"
                                        value={searchInput}
                                        onChange={(e) => setSearchInput(e.target.value)}
                                        placeholder="Search products, brands, sizes…"
                                        className="w-full rounded-full border border-stone-200 bg-stone-50 py-2.5 pl-10 pr-4 text-sm text-stone-900 placeholder:text-stone-400 focus:border-market focus:bg-white focus:outline-none focus:ring-1 focus:ring-market"
                                    />
                                </div>
                                <div className="flex shrink-0 items-center gap-1">
                                    <button
                                        type="button"
                                        className="rounded-full p-2 text-stone-600 transition hover:bg-stone-100"
                                        aria-label="Wishlist"
                                    >
                                        <IconHeart className="h-5 w-5" />
                                    </button>
                                    <button
                                        type="button"
                                        onClick={openCart}
                                        className="relative rounded-full p-2 text-stone-600 transition hover:bg-stone-100"
                                        aria-label="Cart"
                                    >
                                        <IconCart className="h-5 w-5" />
                                        {cartCount > 0 && (
                                            <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-market px-1 text-[10px] font-bold leading-none text-white">
                                                {cartCount > 99 ? '99+' : cartCount}
                                            </span>
                                        )}
                                    </button>
                                    <Link
                                        href={route('login')}
                                        className="rounded-full p-2 text-stone-600 transition hover:bg-stone-100"
                                        aria-label="Account"
                                    >
                                        <IconUser className="h-5 w-5" />
                                    </Link>
                                </div>
                            </form>
                        </div>
                    </div>
                </header>

                {flash?.error ? (
                    <div className="border-b border-red-200 bg-red-50" role="alert">
                        <p className="mx-auto max-w-7xl px-4 py-3 text-sm font-medium text-red-900 sm:px-6 lg:px-8">
                            {flash.error}
                        </p>
                    </div>
                ) : null}

                <div className="border-b border-stone-200/90 bg-white/90">
                    <div className="mx-auto max-w-7xl px-4 py-2.5 sm:px-6 lg:px-8">
                        <Breadcrumbs
                            tone="shop"
                            items={[
                                { label: 'Home', href: '/' },
                                { label: 'Shop' },
                            ]}
                        />
                    </div>
                </div>

                <div className="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 border-b border-stone-200/80 pb-6 sm:flex-row sm:items-end sm:justify-between">
                        <div className="min-w-0">
                            <h1 className="text-xl font-bold tracking-tight text-stone-900 sm:text-3xl">
                                {search_query ? (
                                    <>
                                        {result_count} results for &apos;{search_query}&apos;
                                    </>
                                ) : showResultCount ? (
                                    <>{result_count} products</>
                                ) : (
                                    <>All products</>
                                )}
                            </h1>
                            <p className="mt-1 text-sm text-stone-600 sm:text-base">
                                Thoughtfully crafted items for little explorers.
                            </p>
                        </div>
                        <div className="flex w-full items-center justify-between gap-2 sm:w-auto sm:justify-end">
                            <label htmlFor="sort" className="text-sm text-stone-600">
                                Sort by:
                            </label>
                            <select
                                id="sort"
                                value={applied_filters.sort}
                                onChange={(e) => applyShopFilters({ sort: e.target.value })}
                                className="min-w-0 flex-1 rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm font-medium text-stone-800 shadow-sm focus:border-market focus:outline-none focus:ring-1 focus:ring-market sm:flex-none"
                            >
                                <option value="newest">Newest</option>
                                <option value="relevance">Relevance</option>
                                <option value="price_low">Price: low to high</option>
                                <option value="price_high">Price: high to low</option>
                            </select>
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                        {filter_options.categories.length > 0 || filter_options.conditions.length > 0 ? (
                            <div
                                className="scrollbar-hide -mx-4 flex gap-2 overflow-x-auto overscroll-x-contain px-4 pb-1 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0"
                                style={{ WebkitOverflowScrolling: 'touch', scrollbarWidth: 'none' }}
                            >
                                {filter_options.categories.map((cat) => (
                                    <button
                                        key={cat.id}
                                        type="button"
                                        onClick={() => toggleCategory(cat.id)}
                                        className={`shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition ${
                                            applied_filters.category === cat.id
                                                ? 'bg-market-muted text-market ring-2 ring-market/30'
                                                : 'bg-white text-stone-600 ring-1 ring-stone-200 hover:bg-stone-50'
                                        }`}
                                    >
                                        {cat.label}
                                        <span className="ml-1 text-stone-400">({cat.count})</span>
                                    </button>
                                ))}
                                {filter_options.conditions.map((cond) => (
                                    <button
                                        key={cond.id}
                                        type="button"
                                        onClick={() => toggleCondition(cond.id)}
                                        className={`shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition ${
                                            applied_filters.condition === cond.id
                                                ? 'bg-market-muted text-market ring-2 ring-market/30'
                                                : 'bg-white text-stone-600 ring-1 ring-stone-200 hover:bg-stone-50'
                                        }`}
                                    >
                                        {cond.id === 'new' ? 'New only' : cond.label}
                                        <span className="ml-1 text-stone-400">({cond.count})</span>
                                    </button>
                                ))}
                            </div>
                        ) : (
                            <div className="flex-1" />
                        )}

                        <div className="flex shrink-0 items-center justify-end gap-2">
                            {hasActiveFilters && (
                                <button
                                    type="button"
                                    onClick={clearAllFilters}
                                    className="text-sm font-medium text-stone-500 transition hover:text-stone-800"
                                >
                                    Clear all
                                </button>
                            )}
                            <button
                                type="button"
                                onClick={() => setFiltersOpen(true)}
                                className="inline-flex items-center gap-2 rounded-lg border border-stone-200 bg-white px-3.5 py-2 text-sm font-semibold text-stone-800 shadow-sm transition hover:border-market/40 hover:text-market"
                            >
                                <IconSliders className="h-4 w-4" />
                                Filters
                                {activeDrawerFilters > 0 && (
                                    <span className="flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-market px-1.5 text-[10px] font-bold text-white">
                                        {activeDrawerFilters}
                                    </span>
                                )}
                            </button>
                        </div>
                    </div>

                    <main className="mt-8">
                        {items.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-stone-200 bg-white px-6 py-16 text-center shadow-sm">
                                <p className="text-lg font-semibold text-stone-900">
                                    {hasActiveFilters || search_query ? 'No matching products' : 'No products yet'}
                                </p>
                                <p className="mt-2 text-sm text-stone-600">
                                    {hasActiveFilters || search_query
                                        ? 'Try adjusting your filters or search term.'
                                        : 'Check back soon — our makers are adding new treasures.'}
                                </p>
                                {(hasActiveFilters || search_query) && (
                                    <button
                                        type="button"
                                        onClick={clearAllFilters}
                                        className="mt-6 rounded-lg bg-market px-4 py-2 text-sm font-semibold text-white transition hover:bg-market/90"
                                    >
                                        Clear filters
                                    </button>
                                )}
                            </div>
                        ) : (
                            <>
                                <div className="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                    {displayItems.map((p) => {
                                        const soldOut = isSoldOut(p);

                                        return (
                                            <article
                                                key={p.id}
                                                className={`group relative overflow-hidden rounded-2xl border border-stone-200/90 bg-white shadow-md ring-0 transition-all duration-300 ease-out will-change-transform ${
                                                    soldOut
                                                        ? 'opacity-95'
                                                        : 'hover:-translate-y-2 hover:border-market/45 hover:shadow-2xl hover:shadow-market/20 hover:ring-2 hover:ring-market/25'
                                                }`}
                                            >
                                                <div className="relative z-0">
                                                    <div className="relative aspect-square overflow-hidden bg-stone-100">
                                                        <img
                                                            src={p.image}
                                                            alt=""
                                                            loading="lazy"
                                                            className={`h-full w-full object-cover transition-transform duration-500 ease-out will-change-transform ${
                                                                soldOut
                                                                    ? 'opacity-60 grayscale-[35%]'
                                                                    : 'group-hover:scale-110'
                                                            }`}
                                                        />
                                                        {soldOut && (
                                                            <div className="pointer-events-none absolute inset-0 z-10 flex items-center justify-center bg-stone-900/20">
                                                                <span className="rounded-lg bg-stone-900 px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] text-white shadow-lg">
                                                                    Sold out
                                                                </span>
                                                            </div>
                                                        )}
                                                        {p.badges?.length > 0 && (
                                                            <div className="pointer-events-none absolute left-2 top-2 flex flex-wrap gap-1">
                                                                {p.badges.map((b) => (
                                                                    <span
                                                                        key={b.text}
                                                                        className={`rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ${badgeClasses(
                                                                            b.variant
                                                                        )}`}
                                                                    >
                                                                        {b.text}
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div className="space-y-2 p-4 pb-3">
                                                        <div className="flex items-start justify-between gap-2">
                                                            <h2 className="text-sm font-semibold leading-snug text-stone-900 sm:text-base">
                                                                {p.name}
                                                            </h2>
                                                            <ProductPrice
                                                                price={p.price}
                                                                compareAtPrice={p.compare_at_price}
                                                                className="shrink-0"
                                                                priceClassName="text-sm font-bold text-stone-900"
                                                            />
                                                        </div>
                                                        <p className="text-xs text-stone-500">
                                                            {p.brand ? (
                                                                <>
                                                                    <span className="font-medium text-stone-700">
                                                                        {p.brand}
                                                                    </span>
                                                                    <span className="text-stone-400"> · </span>
                                                                </>
                                                            ) : null}
                                                            by{' '}
                                                            {p.shop_slug ? (
                                                                <Link
                                                                    href={route('shops.show', p.shop_slug)}
                                                                    className="relative z-20 text-[#5c4d3d] hover:text-market hover:underline"
                                                                    onClick={(e) => e.stopPropagation()}
                                                                >
                                                                    {p.maker}
                                                                </Link>
                                                            ) : (
                                                                p.maker
                                                            )}
                                                        </p>
                                                        {(p.condition_label || p.size) && (
                                                            <p className="text-xs font-medium text-stone-600">
                                                                {[
                                                                    p.condition_label && `Condition: ${p.condition_label}`,
                                                                    p.size && `Size: ${p.size}`,
                                                                ]
                                                                    .filter(Boolean)
                                                                    .join(' · ')}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                                <Link
                                                    href={route('shop.show', p.id)}
                                                    className="absolute inset-0 z-10 rounded-2xl focus:outline-none focus:ring-2 focus:ring-market focus:ring-offset-2"
                                                    aria-label={`View details for ${p.name}`}
                                                />
                                                <button
                                                    type="button"
                                                    className="absolute right-2 top-2 z-20 flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-stone-600 shadow-md ring-1 ring-stone-200/80 transition hover:text-rose-500"
                                                    aria-label="Add to wishlist"
                                                >
                                                    <IconHeart className="h-4 w-4" />
                                                </button>
                                                <div className="relative z-20 px-4 pb-4">
                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            e.stopPropagation();
                                                            if (soldOut) {
                                                                return;
                                                            }
                                                            addItem({
                                                                productId: p.id,
                                                                name: p.name,
                                                                image: p.image,
                                                                priceLabel: p.price,
                                                                attributes: [
                                                                    p.brand &&
                                                                        `BRAND: ${String(p.brand).toUpperCase()}`,
                                                                    p.size &&
                                                                        `SIZE: ${String(p.size).toUpperCase()}`,
                                                                ]
                                                                    .filter(Boolean)
                                                                    .join(' • '),
                                                                stockQuantity: p.stock_quantity,
                                                                vendorUserId: p.vendor_user_id,
                                                                vendorName: p.maker,
                                                                vendorSlug: p.shop_slug,
                                                            });
                                                        }}
                                                        disabled={
                                                            soldOut ||
                                                            getRemainingStock(p.id, p.stock_quantity) < 1
                                                        }
                                                        className="flex w-full items-center justify-center gap-2 rounded-xl bg-[#5c4d3d] py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#4a3e32] disabled:cursor-not-allowed disabled:bg-stone-400 disabled:opacity-100"
                                                    >
                                                        {!soldOut && <IconCart className="h-4 w-4" />}
                                                        {soldOut
                                                            ? 'Sold out'
                                                            : getRemainingStock(p.id, p.stock_quantity) < 1
                                                              ? 'Max in cart'
                                                              : 'Add to Cart'}
                                                    </button>
                                                </div>
                                            </article>
                                        );
                                    })}
                                </div>

                                {/* Mobile: infinite scroll with a manual fallback button */}
                                {last_page > 1 && (
                                    <div className="mt-8 sm:hidden">
                                        <div ref={loadMoreSentinelRef} aria-hidden />
                                        {hasMore ? (
                                            <button
                                                type="button"
                                                onClick={loadMore}
                                                disabled={loadingMore}
                                                className="flex w-full items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white py-3 text-sm font-semibold text-stone-800 shadow-sm transition hover:border-market hover:text-market disabled:cursor-wait disabled:opacity-60"
                                            >
                                                {loadingMore ? (
                                                    <>
                                                        <span
                                                            className="h-4 w-4 animate-spin rounded-full border-2 border-stone-300 border-t-market"
                                                            aria-hidden
                                                        />
                                                        Loading more…
                                                    </>
                                                ) : showResultCount ? (
                                                    <>Load more ({displayItems.length} of {result_count})</>
                                                ) : (
                                                    <>Load more</>
                                                )}
                                            </button>
                                        ) : (
                                            <p className="text-center text-sm text-stone-500">
                                                {showResultCount
                                                    ? `You've seen all ${result_count} products.`
                                                    : "You've reached the end."}
                                            </p>
                                        )}
                                    </div>
                                )}

                                {/* Desktop: numbered pagination */}
                                {last_page > 1 && (
                                    <nav
                                        className="mt-12 hidden flex-wrap items-center justify-center gap-2 sm:flex"
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
                            </>
                        )}
                    </main>
                </div>

                {filtersOpen && (
                    <div className="fixed inset-0 z-50 flex items-end justify-center sm:items-stretch sm:justify-end">
                        <button
                            type="button"
                            className="absolute inset-0 bg-stone-900/40"
                            aria-label="Close filters"
                            onClick={() => setFiltersOpen(false)}
                        />
                        <div className="relative flex max-h-[92vh] w-full flex-col rounded-t-2xl bg-white shadow-2xl sm:h-full sm:max-h-none sm:max-w-sm sm:rounded-none">
                            <div className="border-b border-stone-200 px-4 py-3 sm:px-5 sm:py-4">
                                <div className="mx-auto mb-3 h-1 w-10 rounded-full bg-stone-300 sm:hidden" aria-hidden />
                                <div className="flex items-center justify-between">
                                    <h2 className="text-lg font-bold text-stone-900">Filters</h2>
                                    <button
                                        type="button"
                                        onClick={() => setFiltersOpen(false)}
                                        className="rounded-full p-2 text-stone-500 transition hover:bg-stone-100"
                                        aria-label="Close"
                                    >
                                        <IconX className="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            <div className="flex-1 space-y-8 overflow-y-auto overscroll-contain px-4 py-6 sm:px-5">
                                <div>
                                    <h3 className="text-xs font-bold uppercase tracking-wider text-stone-500">
                                        Price range
                                    </h3>
                                    <div className="mt-3">
                                        <input
                                            type="range"
                                            min="0"
                                            max={filter_options.price_ceiling}
                                            value={draftPriceMax}
                                            onChange={(e) => setDraftPriceMax(Number(e.target.value))}
                                            className="w-full accent-market"
                                        />
                                        <div className="mt-1 flex justify-between text-xs text-stone-500">
                                            <span>GHS 0</span>
                                            <span>
                                                {draftPriceMax >= filter_options.price_ceiling
                                                    ? `GHS ${filter_options.price_ceiling}+`
                                                    : `Up to GHS ${draftPriceMax}`}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <span className="text-sm font-medium text-stone-800">Eco-friendly only</span>
                                        <p className="mt-0.5 text-xs text-stone-500">
                                            Organic, recycled, plastic-free, or fair trade
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        role="switch"
                                        aria-checked={draftEco}
                                        onClick={() => setDraftEco(!draftEco)}
                                        className={`relative h-7 w-12 shrink-0 rounded-full transition ${
                                            draftEco ? 'bg-market' : 'bg-stone-300'
                                        }`}
                                    >
                                        <span
                                            className={`absolute top-0.5 h-6 w-6 rounded-full bg-white shadow transition ${
                                                draftEco ? 'left-5' : 'left-0.5'
                                            }`}
                                        />
                                    </button>
                                </div>

                                {filter_options.conditions.length > 0 && (
                                    <div>
                                        <h3 className="text-xs font-bold uppercase tracking-wider text-stone-500">
                                            Condition
                                        </h3>
                                        <ul className="mt-3 space-y-2.5">
                                            {filter_options.conditions.map((cond) => (
                                                <li key={cond.id} className="flex items-center gap-2">
                                                    <input
                                                        id={`condition-${cond.id}`}
                                                        type="radio"
                                                        name="condition"
                                                        checked={draftCondition === cond.id}
                                                        onChange={() => setDraftCondition(cond.id)}
                                                        className="border-stone-300 text-market focus:ring-market"
                                                    />
                                                    <label
                                                        htmlFor={`condition-${cond.id}`}
                                                        className="text-sm text-stone-800"
                                                    >
                                                        {cond.id === 'new' ? 'New only' : cond.label}
                                                        <span className="ml-1 text-stone-400">({cond.count})</span>
                                                    </label>
                                                </li>
                                            ))}
                                            <li className="flex items-center gap-2">
                                                <input
                                                    id="condition-any"
                                                    type="radio"
                                                    name="condition"
                                                    checked={draftCondition === null}
                                                    onChange={() => setDraftCondition(null)}
                                                    className="border-stone-300 text-market focus:ring-market"
                                                />
                                                <label htmlFor="condition-any" className="text-sm text-stone-800">
                                                    Any condition
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                )}

                                {filter_options.makers.length > 0 && (
                                    <div>
                                        <h3 className="text-xs font-bold uppercase tracking-wider text-stone-500">
                                            Vendors
                                        </h3>
                                        <ul className="mt-3 space-y-2.5">
                                            {filter_options.makers.map((maker) => (
                                                <li key={maker.id} className="flex items-center gap-2">
                                                    <input
                                                        id={`maker-${maker.id}`}
                                                        type="radio"
                                                        name="maker"
                                                        checked={draftMaker === maker.id}
                                                        onChange={() => setDraftMaker(maker.id)}
                                                        className="border-stone-300 text-market focus:ring-market"
                                                    />
                                                    <label
                                                        htmlFor={`maker-${maker.id}`}
                                                        className="text-sm text-stone-800"
                                                    >
                                                        {maker.name}
                                                    </label>
                                                </li>
                                            ))}
                                            <li className="flex items-center gap-2">
                                                <input
                                                    id="maker-any"
                                                    type="radio"
                                                    name="maker"
                                                    checked={draftMaker === null}
                                                    onChange={() => setDraftMaker(null)}
                                                    className="border-stone-300 text-market focus:ring-market"
                                                />
                                                <label htmlFor="maker-any" className="text-sm text-stone-800">
                                                    Any vendor
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                )}
                            </div>

                            <div className="flex gap-3 border-t border-stone-200 px-4 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:px-5">
                                <button
                                    type="button"
                                    onClick={clearDrawerFilters}
                                    className="flex-1 rounded-lg border border-stone-200 px-4 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50"
                                >
                                    Reset
                                </button>
                                <button
                                    type="button"
                                    onClick={applyDrawerFilters}
                                    className="flex-1 rounded-lg bg-market px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-market/90"
                                >
                                    Show results
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                <SiteFooter />
            </div>
        </>
    );
}
