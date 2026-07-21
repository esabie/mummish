import { Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import LogoMark from '@/Components/LogoMark';
import Modal from '@/Components/Modal';
import ProductPrice from '@/Components/ProductPrice';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';
import { useCart } from '@/context/CartContext';
import { Dialog } from '@headlessui/react';

const FEATURED_CATEGORY_LIMIT = 6;

function IconCart(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
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

function productHref(product) {
    if (product.shop_slug) {
        return route('shops.products.show', [product.shop_slug, product.id]);
    }
    return route('shop.show', product.id);
}

/** Edge-to-edge horizontal scroll on mobile; hidden from md up. */
function MobileCarousel({ children, className = '', ariaLabel }) {
    return (
        <div
            className={`scrollbar-hide -mx-4 flex gap-3 overflow-x-auto overscroll-x-contain px-4 pb-1 snap-x snap-mandatory md:hidden ${className}`}
            style={{ WebkitOverflowScrolling: 'touch', scrollbarWidth: 'none', msOverflowStyle: 'none' }}
            role="region"
            aria-label={ariaLabel}
        >
            {children}
        </div>
    );
}

function SectionHeading({ title, action }) {
    return (
        <div className="mb-4 flex items-end justify-between gap-3 sm:mb-5">
            <h2 className="text-lg font-bold tracking-tight text-neutral-900 sm:text-2xl">{title}</h2>
            {action}
        </div>
    );
}

function CategoryCard({ cat }) {
    return (
        <Link
            href={route('shop.index', { category: cat.id })}
            className="group flex min-w-0 flex-col"
        >
            <div className="aspect-square overflow-hidden rounded-2xl bg-neutral-100 ring-1 ring-neutral-200/80 transition group-hover:ring-market/40 group-active:scale-[0.98]">
                <img
                    src={cat.image}
                    alt=""
                    loading="lazy"
                    decoding="async"
                    className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                />
            </div>
            <p className="mt-2 line-clamp-2 text-xs font-semibold leading-snug text-neutral-900 sm:mt-3 sm:text-sm">
                {cat.label}
            </p>
            <p className="mt-0.5 text-[11px] text-neutral-500 sm:text-xs">
                {cat.count} {cat.count === 1 ? 'item' : 'items'}
            </p>
        </Link>
    );
}

function StoreAvatar({ store }) {
    return (
        <Link
            href={route('shops.show', store.slug)}
            className="group flex min-w-[4.25rem] max-w-[5rem] shrink-0 snap-start flex-col items-center sm:min-w-[5rem] sm:max-w-[5.5rem]"
            title={store.name}
        >
            {store.image ? (
                <div className="h-16 w-16 overflow-hidden rounded-full bg-neutral-100 ring-1 ring-neutral-200/80 transition group-hover:ring-market/40 group-active:scale-95 sm:h-20 sm:w-20">
                    <img
                        src={store.image}
                        alt=""
                        loading="lazy"
                        decoding="async"
                        className="h-full w-full object-cover"
                    />
                </div>
            ) : (
                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-neutral-200 text-base font-bold text-neutral-600 ring-1 ring-neutral-200/80 transition group-hover:ring-market/40 group-active:scale-95 sm:h-20 sm:w-20 sm:text-lg">
                    {store.initial}
                </div>
            )}
            <p className="mt-2 line-clamp-2 w-full text-center text-[10px] leading-tight text-neutral-800 sm:text-xs">
                {store.name}
            </p>
        </Link>
    );
}

function ProductCard({ product }) {
    return (
        <article className="overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm transition active:scale-[0.99]">
            <Link href={productHref(product)} className="block">
                <div className="relative aspect-square bg-neutral-100">
                    <img
                        src={product.image}
                        alt=""
                        loading="lazy"
                        decoding="async"
                        className="h-full w-full object-cover"
                    />
                    {product.badges?.length > 0 && (
                        <span className="absolute left-2 top-2 rounded-md bg-white/95 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-neutral-800 shadow-sm">
                            {product.badges[0].text}
                        </span>
                    )}
                </div>
                <div className="p-3 sm:p-4">
                    <p className="line-clamp-2 text-xs font-semibold text-neutral-900 sm:text-sm">{product.name}</p>
                    <ProductPrice
                        price={product.price}
                        compareAtPrice={product.compare_at_price}
                        className="mt-1"
                    />
                    <p className="mt-1 truncate text-[11px] text-neutral-500 sm:text-xs">by {product.maker}</p>
                </div>
            </Link>
        </article>
    );
}

export default function Welcome({
    auth,
    canLogin,
    canRegister,
    categories = [],
    featured_stores = [],
    popular_products = [],
}) {
    const [searchInput, setSearchInput] = useState('');
    const [categoriesModalOpen, setCategoriesModalOpen] = useState(false);
    const { openCart, count: cartCount } = useCart();

    const featuredCategories = useMemo(() => {
        const withItems = categories
            .filter((cat) => cat.count > 0)
            .sort((a, b) => b.count - a.count);
        const withoutItems = categories.filter((cat) => cat.count === 0);

        return [...withItems, ...withoutItems].slice(0, FEATURED_CATEGORY_LIMIT);
    }, [categories]);

    const submitSearch = (e) => {
        e.preventDefault();
        const q = searchInput.trim();
        router.get(route('shop.index'), { page: 1, ...(q ? { q } : {}) });
    };

    return (
        <>
            <SeoHead
                title=""
                description="Mummish is Ghana's marketplace for families — shop nursing, feeding, clothing, toys, and more from trusted local sellers."
                url="/"
                image="/images/logo.png"
            />

            <div className="min-h-screen bg-white text-neutral-900 antialiased">
                {/* <div className="border-b border-neutral-200 bg-market-muted/80">
                    <div className="mx-auto flex max-w-7xl flex-col items-center justify-center gap-1 px-4 py-2 text-center text-xs font-medium text-market sm:flex-row sm:gap-x-4 sm:text-sm">
                        <span>Save 10% on your order</span>
                        <span className="hidden text-neutral-400 sm:inline" aria-hidden>
                            |
                        </span>
                        <span className="text-market">Use code WELCOME10 at checkout (discount on items subtotal)</span>
                    </div>
                </div> */}

                <header className="sticky top-0 z-40 border-b border-neutral-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="flex w-full items-center gap-2 py-3 sm:gap-3 lg:gap-6">
                            <LogoMark iconClassName="h-16 w-auto sm:h-20" />

                            <form onSubmit={submitSearch} className="hidden min-w-0 flex-1 md:block">
                                <div className="relative mx-auto w-full max-w-xl">
                                    <label htmlFor="site-search" className="sr-only">
                                        Search
                                    </label>
                                    <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-neutral-400">
                                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </span>
                                    <input
                                        id="site-search"
                                        type="search"
                                        value={searchInput}
                                        onChange={(e) => setSearchInput(e.target.value)}
                                        placeholder="Search products, brands, sizes…"
                                        className="w-full rounded-lg border border-neutral-300 bg-neutral-50 py-2.5 pl-10 pr-4 text-sm text-neutral-900 placeholder:text-neutral-500 focus:border-market focus:bg-white focus:outline-none focus:ring-1 focus:ring-market"
                                    />
                                </div>
                            </form>

                            <div className="ml-auto flex shrink-0 items-center gap-0.5 sm:gap-2">
                                <Link
                                    href={route('shop.index')}
                                    className="rounded-full px-2.5 py-1.5 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-100 sm:px-3 sm:text-sm"
                                >
                                    Shop
                                </Link>
                                {canRegister && (
                                    <Link
                                        href={route('vendor.signup')}
                                        className="hidden rounded-full border border-market px-3 py-1.5 text-sm font-semibold text-market transition hover:bg-market-muted sm:inline-block"
                                    >
                                        Sell
                                    </Link>
                                )}
                                <button
                                    type="button"
                                    onClick={openCart}
                                    className="relative rounded-full p-2 text-neutral-700 transition hover:bg-neutral-100 active:bg-neutral-100"
                                    aria-label="Shopping bag"
                                >
                                    <IconCart className="h-6 w-6" />
                                    {cartCount > 0 && (
                                        <span className="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-market px-1 text-[10px] font-bold leading-none text-white">
                                            {cartCount > 99 ? '99+' : cartCount}
                                        </span>
                                    )}
                                </button>
                                {auth.user ? (
                                    <Link
                                        href={route('dashboard')}
                                        className="flex h-9 w-9 items-center justify-center rounded-full bg-neutral-200 text-sm font-semibold text-neutral-800 transition hover:bg-neutral-300"
                                        aria-label="Account"
                                    >
                                        {auth.user.name?.charAt(0)?.toUpperCase() ?? '?'}
                                    </Link>
                                ) : canLogin ? (
                                    <Link
                                        href={route('login')}
                                        className="rounded-full p-2 text-neutral-700 transition hover:bg-neutral-100"
                                        aria-label="Sign in"
                                    >
                                        <IconUser className="h-6 w-6" />
                                    </Link>
                                ) : null}
                            </div>
                        </div>

                        <form onSubmit={submitSearch} className="border-t border-neutral-100 pb-3 md:hidden">
                            <label htmlFor="site-search-mobile" className="sr-only">
                                Search
                            </label>
                            <input
                                id="site-search-mobile"
                                type="search"
                                value={searchInput}
                                onChange={(e) => setSearchInput(e.target.value)}
                                placeholder="Search products…"
                                className="mt-2 w-full rounded-lg border border-neutral-300 bg-neutral-50 py-2.5 px-3 text-base focus:border-market focus:outline-none focus:ring-1 focus:ring-market"
                            />
                        </form>
                    </div>
                </header>

                <section className="relative w-full overflow-hidden bg-[#c3e9fa]">
                    <div className="relative min-h-[22rem] w-full sm:min-h-[28rem] lg:min-h-[32rem]">
                        <img
                            src="/images/hero-one-stop.png"
                            alt=""
                            className="absolute inset-0 h-full w-full object-cover object-center"
                        />
                        <div className="absolute inset-0 bg-[#c3e9fa]/35 sm:bg-[#c3e9fa]/20" aria-hidden />
                        <div className="relative z-10 flex min-h-[22rem] flex-col items-center justify-center px-6 py-14 text-center sm:min-h-[28rem] sm:py-16 lg:min-h-[32rem]">
                            <h1 className="max-w-3xl text-3xl font-extrabold tracking-tight text-neutral-950 sm:text-5xl lg:text-6xl">
                                Marketplace for the modern mother.
                            </h1>
                            <p className="mt-4 max-w-xl text-sm leading-relaxed text-neutral-800 sm:text-base">
                            Connect with us to unlock unbeatable deals on kids products 
                            and Services while supporting fellow mothers and sustainability.
                            </p>
                            <Link
                                href={route('shop.index')}
                                className="mt-8 inline-flex items-center gap-2 rounded-full bg-neutral-950 px-7 py-3.5 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-neutral-800 active:scale-[0.98]"
                            >
                                <IconCart className="h-5 w-5" aria-hidden />
                                Shop
                            </Link>
                        </div>
                    </div>
                </section>

                <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
                    {categories.length > 0 && (
                        <section className="mb-10 sm:mb-16">
                            <SectionHeading
                                title="Shop by category"
                                action={
                                    <button
                                        type="button"
                                        onClick={() => setCategoriesModalOpen(true)}
                                        className="shrink-0 text-sm font-semibold text-market hover:underline"
                                    >
                                        Browse all Categories
                                    </button>
                                }
                            />

                            <MobileCarousel ariaLabel="Shop by category">
                                {featuredCategories.map((cat) => (
                                    <div key={cat.id} className="w-[38vw] min-w-[7.5rem] max-w-[9.5rem] shrink-0 snap-start">
                                        <CategoryCard cat={cat} />
                                    </div>
                                ))}
                            </MobileCarousel>

                            <div className="hidden gap-4 md:grid md:grid-cols-3 lg:grid-cols-6">
                                {featuredCategories.map((cat) => (
                                    <CategoryCard key={cat.id} cat={cat} />
                                ))}
                            </div>

                            <Modal
                                show={categoriesModalOpen}
                                onClose={() => setCategoriesModalOpen(false)}
                                maxWidth="4xl"
                            >
                                <div className="p-6">
                                    <div className="mb-6 flex items-start justify-between gap-4">
                                        <Dialog.Title className="text-xl font-bold text-neutral-900">
                                            Select A Category
                                        </Dialog.Title>
                                        <button
                                            type="button"
                                            onClick={() => setCategoriesModalOpen(false)}
                                            className="rounded-lg p-1.5 text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-800"
                                            aria-label="Close"
                                        >
                                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div className="grid max-h-[70vh] grid-cols-2 gap-4 overflow-y-auto sm:grid-cols-3 lg:grid-cols-4">
                                        {categories.map((cat) => (
                                            <CategoryCard key={cat.id} cat={cat} />
                                        ))}
                                    </div>
                                </div>
                            </Modal>
                        </section>
                    )}

                    {featured_stores.length > 0 && (
                        <section className="mb-10 sm:mb-16">
                            <SectionHeading
                                title="Featured stores"
                                action={
                                    <Link
                                        href={route('shops.index')}
                                        className="shrink-0 text-sm font-semibold text-market hover:underline"
                                    >
                                        View all stores
                                    </Link>
                                }
                            />

                            <MobileCarousel ariaLabel="Featured stores" className="gap-4">
                                {featured_stores.map((store) => (
                                    <StoreAvatar key={store.slug} store={store} />
                                ))}
                            </MobileCarousel>

                            <div className="hidden flex-wrap gap-x-6 gap-y-5 md:flex lg:gap-x-8">
                                {featured_stores.map((store) => (
                                    <StoreAvatar key={store.slug} store={store} />
                                ))}
                            </div>
                        </section>
                    )}

                    {popular_products.length > 0 && (
                        <section className="mb-10 sm:mb-16">
                            <SectionHeading
                                title="Popular now"
                                action={
                                    <Link href={route('shop.index')} className="shrink-0 text-sm font-semibold text-market hover:underline">
                                        Shop all
                                    </Link>
                                }
                            />

                            <div className="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                                {popular_products.map((product) => (
                                    <ProductCard key={product.id} product={product} />
                                ))}
                            </div>
                        </section>
                    )}

                    {categories.length === 0 && featured_stores.length === 0 && popular_products.length === 0 && (
                        <section className="rounded-2xl border border-dashed border-neutral-200 bg-neutral-50 px-4 py-12 text-center sm:px-6 sm:py-16">
                            <h2 className="text-lg font-bold text-neutral-900 sm:text-xl">Our makers are getting started</h2>
                            <p className="mt-2 text-sm text-neutral-600 sm:text-base">
                                New stores and products are on the way. Check back soon or start selling on Mummish.
                            </p>
                            {canRegister && (
                                <Link
                                    href={route('vendor.signup')}
                                    className="mt-6 inline-flex w-full items-center justify-center rounded-full bg-market px-6 py-3 text-sm font-semibold text-white transition hover:bg-market-hover sm:w-auto"
                                >
                                    Become a seller
                                </Link>
                            )}
                        </section>
                    )}

                    <section className="rounded-2xl bg-market-muted/50 px-4 py-8 sm:px-10 sm:py-12">
                        <div className="mx-auto max-w-2xl text-center">
                            <h2 className="text-xl font-bold tracking-tight text-neutral-900 sm:text-2xl">Sell with Mummish</h2>
                            <p className="mt-3 text-sm text-neutral-600 sm:text-base">
                                List your products, reach parents across Ghana, and grow your own storefront on our marketplace.
                            </p>
                            {canRegister && (
                                <Link
                                    href={route('vendor.signup')}
                                    className="mt-6 inline-flex w-full items-center justify-center rounded-full border-2 border-neutral-900 bg-white px-6 py-3 text-sm font-semibold text-neutral-900 transition hover:bg-neutral-50 sm:w-auto"
                                >
                                    Start selling
                                </Link>
                            )}
                        </div>
                    </section>
                </main>

                <SiteFooter />

                <style>{`
                    .scrollbar-hide::-webkit-scrollbar {
                        display: none;
                    }
                `}</style>
            </div>
        </>
    );
}
