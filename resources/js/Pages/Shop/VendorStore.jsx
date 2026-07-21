import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Breadcrumbs from '@/Components/Breadcrumbs';
import LogoMark from '@/Components/LogoMark';
import ProductPrice from '@/Components/ProductPrice';
import SeoHead from '@/Components/SeoHead';
import ShareButton from '@/Components/ShareButton';
import SiteFooter from '@/Components/SiteFooter';
import { useCart } from '@/context/CartContext';

function IconSearch(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    );
}

function IconCart(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.5a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
            />
        </svg>
    );
}

function isSoldOut(product) {
    return Boolean(product.sold_out) || (product.stock_quantity ?? 0) < 1;
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

export default function VendorStore({ store, search_query, result_count, current_page, last_page, items }) {
    const { openCart, addItem, getRemainingStock, count: cartCount } = useCart();
    const [searchInput, setSearchInput] = useState(search_query);

    useEffect(() => {
        setSearchInput(search_query);
    }, [search_query]);

    const submitSearch = (e) => {
        e.preventDefault();
        const q = searchInput.trim();
        router.get(
            route('shops.show', store.slug),
            { page: 1, ...(q ? { q } : {}) },
            { preserveState: true }
        );
    };

    const pageHref = (page) =>
        route('shops.show', {
            slug: store.slug,
            ...(search_query ? { q: search_query } : {}),
            page,
        });

    const pages = buildPagination(current_page, last_page);

    return (
        <>
            <SeoHead
                title={store.name}
                description={`Shop ${store.name} on Mummish — ${store.category || 'family essentials'} from a trusted Ghanaian seller.`}
                image={store.logo || '/images/logo.png'}
                url={route('shops.show', store.slug)}
            />

            <div className="flex min-h-screen flex-col bg-[#faf9f7] text-stone-900 antialiased">
                <header className="border-b border-stone-200/90 bg-white/95 backdrop-blur">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
                        <Link
                            href={route('shop.index')}
                            className="shrink-0 text-sm font-semibold text-[#5c4d3d] hover:text-market hover:underline"
                        >
                            ← All shops
                        </Link>
                        <LogoMark variant="shop" className="min-w-0 flex-1 justify-center" />
                        <button
                            type="button"
                            onClick={openCart}
                            className="relative shrink-0 rounded-full p-2 text-stone-600 transition hover:bg-stone-100"
                            aria-label="Cart"
                        >
                            <IconCart className="h-5 w-5" />
                            {cartCount > 0 && (
                                <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-market px-1 text-[10px] font-bold leading-none text-white">
                                    {cartCount > 99 ? '99+' : cartCount}
                                </span>
                            )}
                        </button>
                    </div>
                </header>

                <div className="border-b border-stone-200/80 bg-white/95">
                    <div className="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                        <Breadcrumbs
                            tone="shop"
                            className="text-sm text-[#6b5344]"
                            items={[
                                { label: 'Home', href: '/' },
                                { label: 'Shop', href: route('shop.index') },
                                { label: store.name },
                            ]}
                        />
                    </div>
                </div>

                <section className="border-b border-stone-200/80 bg-gradient-to-br from-[#f7f3ee] via-white to-[#f0ebe3]">
                    <div className="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-10 sm:px-6 sm:py-12 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                        <div className="flex items-center gap-4">
                            {store.logo ? (
                                <div className="h-16 w-16 shrink-0 overflow-hidden rounded-2xl shadow-md ring-1 ring-stone-200/80">
                                    <img
                                        src={store.logo}
                                        alt=""
                                        className="h-full w-full object-cover"
                                    />
                                </div>
                            ) : (
                                <div
                                    className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-[#5c4d3d] text-2xl font-bold text-white shadow-md"
                                    aria-hidden
                                >
                                    {store.initial}
                                </div>
                            )}
                            <div>
                                <p className="text-xs font-bold uppercase tracking-[0.2em] text-stone-500">Vendor store</p>
                                <h1 className="mt-1 text-3xl font-bold tracking-tight text-[#3d3429] sm:text-4xl">
                                    {store.name}
                                </h1>
                                <div className="mt-2 flex flex-wrap items-center gap-3">
                                    <p className="text-sm text-stone-600">
                                        {result_count} {result_count === 1 ? 'product' : 'products'} available
                                    </p>
                                    <ShareButton
                                        url={store.url}
                                        title={store.name}
                                        text={`Browse products from ${store.name} on Mummish`}
                                        label="Share store"
                                    />
                                </div>
                            </div>
                        </div>

                        <form onSubmit={submitSearch} className="w-full lg:max-w-md">
                            <div className="relative">
                                <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-stone-400">
                                    <IconSearch className="h-5 w-5" />
                                </span>
                                <input
                                    type="search"
                                    value={searchInput}
                                    onChange={(e) => setSearchInput(e.target.value)}
                                    placeholder={`Search in ${store.name}…`}
                                    className="w-full rounded-xl border border-stone-200 bg-white py-3 pl-10 pr-4 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-market focus:outline-none focus:ring-2 focus:ring-market/20"
                                />
                            </div>
                        </form>
                    </div>
                </section>

                <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-10 sm:px-6 lg:px-8">
                    {items.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-stone-300 bg-white/70 px-6 py-16 text-center">
                            <p className="text-lg font-semibold text-stone-800">No products found</p>
                            <p className="mt-2 text-sm text-stone-500">
                                {search_query
                                    ? `No results for "${search_query}" in this store.`
                                    : `${store.name} has not listed any products yet.`}
                            </p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6 lg:grid-cols-4 xl:grid-cols-5">
                            {items.map((p) => {
                                const soldOut = isSoldOut(p);

                                return (
                                    <article
                                        key={p.id}
                                        className="group relative flex flex-col overflow-hidden rounded-2xl border border-stone-200/80 bg-white shadow-sm transition hover:shadow-md"
                                    >
                                        <div className="relative aspect-[4/5] overflow-hidden bg-stone-100">
                                            <img
                                                src={p.image}
                                                alt=""
                                                className={`h-full w-full object-cover transition duration-300 group-hover:scale-[1.02] ${soldOut ? 'opacity-60 grayscale-[35%]' : ''}`}
                                            />
                                            {soldOut && (
                                                <span className="absolute left-3 top-3 rounded-md bg-stone-900 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white">
                                                    Sold out
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex flex-1 flex-col p-4">
                                            <h2 className="line-clamp-2 text-sm font-bold text-stone-900">{p.name}</h2>
                                            <ProductPrice
                                                price={p.price}
                                                compareAtPrice={p.compare_at_price}
                                                className="mt-2"
                                                priceClassName="text-base font-bold text-[#5c4d3d]"
                                            />
                                            {(p.condition_label || p.size) && (
                                                <p className="mt-1 text-xs text-stone-500">
                                                    {[
                                                        p.condition_label && `Condition: ${p.condition_label}`,
                                                        p.size && `Size: ${p.size}`,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(' · ')}
                                                </p>
                                            )}
                                        </div>
                                        <Link
                                            href={route('shops.products.show', { slug: store.slug, id: p.id })}
                                            className="absolute inset-0 z-10 rounded-2xl focus:outline-none focus:ring-2 focus:ring-market focus:ring-offset-2"
                                            aria-label={`View details for ${p.name}`}
                                        />
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
                                                            p.brand && `BRAND: ${String(p.brand).toUpperCase()}`,
                                                            p.size && `SIZE: ${String(p.size).toUpperCase()}`,
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' • '),
                                                        stockQuantity: p.stock_quantity,
                                                        vendorUserId: p.vendor_user_id,
                                                        vendorName: p.maker,
                                                        vendorSlug: p.shop_slug ?? store.slug,
                                                    });
                                                }}
                                                disabled={soldOut || getRemainingStock(p.id, p.stock_quantity) < 1}
                                                className="flex w-full items-center justify-center gap-2 rounded-xl bg-[#5c4d3d] py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#4a3e32] disabled:cursor-not-allowed disabled:bg-stone-400"
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
                    )}

                    {last_page > 1 && (
                        <nav className="mt-12 flex flex-wrap items-center justify-center gap-2" aria-label="Pagination">
                            {current_page <= 1 ? (
                                <span className="rounded-lg px-3 py-2 text-sm text-stone-400">Previous</span>
                            ) : (
                                <Link
                                    href={pageHref(current_page - 1)}
                                    className="rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50"
                                >
                                    Previous
                                </Link>
                            )}
                            {pages.map((page, index) =>
                                page === 'ellipsis' ? (
                                    <span key={`ellipsis-${index}`} className="px-2 text-stone-400">
                                        …
                                    </span>
                                ) : (
                                    <Link
                                        key={page}
                                        href={pageHref(page)}
                                        className={`min-w-[2.5rem] rounded-lg px-3 py-2 text-center text-sm font-medium ${
                                            page === current_page
                                                ? 'bg-[#5c4d3d] text-white'
                                                : 'border border-stone-200 bg-white text-stone-700 hover:bg-stone-50'
                                        }`}
                                        aria-current={page === current_page ? 'page' : undefined}
                                    >
                                        {page}
                                    </Link>
                                )
                            )}
                            {current_page >= last_page ? (
                                <span className="rounded-lg px-3 py-2 text-sm text-stone-400">Next</span>
                            ) : (
                                <Link
                                    href={pageHref(current_page + 1)}
                                    className="rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50"
                                >
                                    Next
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
