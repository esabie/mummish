import { Link } from '@inertiajs/react';
import { useState } from 'react';
import Breadcrumbs from '@/Components/Breadcrumbs';
import LogoMark from '@/Components/LogoMark';
import ProductPrice from '@/Components/ProductPrice';
import SeoHead from '@/Components/SeoHead';
import SiteFooter from '@/Components/SiteFooter';
import { useCart } from '@/context/CartContext';

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

function IconHeart(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
            />
        </svg>
    );
}

function IconTruck(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.25 2.25 0 00-1.945-1.125h-9.75a2.25 2.25 0 00-2.25 2.25v10.5A1.125 1.125 0 004.875 18.75H8.25z"
            />
        </svg>
    );
}

function IconCheckVerified({ className = '' }) {
    return (
        <svg viewBox="0 0 20 20" fill="currentColor" className={className} aria-hidden>
            <path
                fillRule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                clipRule="evenodd"
            />
        </svg>
    );
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
        case 'sale':
            return 'bg-red-100 text-red-900 ring-1 ring-red-200/80';
        default:
            return 'bg-stone-200 text-stone-800 ring-1 ring-stone-300/80';
    }
}

function tagToneClasses(tone) {
    switch (tone) {
        case 'mint':
            return 'bg-emerald-50 text-emerald-900 ring-1 ring-emerald-200/70';
        case 'sky':
            return 'bg-sky-50 text-sky-900 ring-1 ring-sky-200/70';
        case 'peach':
            return 'bg-orange-50 text-orange-900 ring-1 ring-orange-200/70';
        default:
            return 'bg-stone-100 text-stone-800 ring-1 ring-stone-200/80';
    }
}

function StarRow({ rating, className = '' }) {
    const full = Math.round(Number(rating) || 0);
    return (
        <div className={`flex items-center gap-0.5 ${className}`} aria-hidden>
            {[1, 2, 3, 4, 5].map((i) => (
                <svg
                    key={i}
                    className={`h-4 w-4 sm:h-5 sm:w-5 ${i <= full ? 'text-amber-400' : 'text-stone-200'}`}
                    viewBox="0 0 24 24"
                    fill="currentColor"
                >
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                </svg>
            ))}
        </div>
    );
}

function ProductDetailRow({ label, value }) {
    if (!value) {
        return null;
    }

    return (
        <div className="flex flex-col gap-0.5 sm:flex-row sm:gap-4">
            <dt className="w-32 shrink-0 text-sm font-medium text-stone-500">{label}</dt>
            <dd className="text-sm font-semibold text-stone-900">{value}</dd>
        </div>
    );
}

function ProductSizeGrid({ sizeOptions, selectedKey, selectedLabel }) {
    if (!sizeOptions?.length || !selectedKey) {
        return null;
    }

    return (
        <div className="mt-8">
            <p className="text-sm font-semibold text-stone-900">Size</p>
            <p className="mt-1 text-xs text-stone-500">This item is available in the size below.</p>
            <div className="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-7">
                {sizeOptions.map((option) => {
                    const isSelected = option.value === selectedKey;
                    return (
                        <div
                            key={option.value}
                            className={`flex min-h-[2.75rem] items-center justify-center rounded-md border px-1 text-center text-xs font-semibold sm:text-sm ${
                                isSelected
                                    ? 'border-stone-900 bg-stone-900 text-white'
                                    : 'border-stone-200 bg-stone-50 text-stone-300'
                            }`}
                            aria-current={isSelected ? 'true' : undefined}
                        >
                            {option.label}
                        </div>
                    );
                })}
            </div>
            {selectedLabel && (
                <p className="mt-3 text-sm font-medium text-stone-700">
                    Selected: <span className="text-stone-900">{selectedLabel}</span>
                </p>
            )}
        </div>
    );
}

export default function ShopShow({ product, store = null }) {
    const gallery = product.gallery?.length ? product.gallery : [product.image].filter(Boolean);
    const [activeImage, setActiveImage] = useState(0);
    const [openAccordion, setOpenAccordion] = useState(null);
    const { openCart, addItem, getRemainingStock, lines, count: cartCount } = useCart();

    const mainSrc = gallery[Math.min(activeImage, gallery.length - 1)] ?? product.image;
    const category = product.category || 'Toys';
    const q = String(category).toLowerCase();
    const stockQuantity = product.stock_quantity ?? 0;
    const soldOut = Boolean(product.sold_out) || stockQuantity < 1;
    const remainingStock = soldOut ? 0 : getRemainingStock(product.id, stockQuantity);
    const inCartQty = lines
        .filter((line) => line.productId === product.id)
        .reduce((sum, line) => sum + line.qty, 0);

    const attributes = [
        product.brand && `BRAND: ${String(product.brand).toUpperCase()}`,
        product.category && `CATEGORY: ${String(product.category).toUpperCase()}`,
        product.size && `SIZE: ${String(product.size).toUpperCase()}`,
        product.maker && `BY ${String(product.maker).toUpperCase()}`,
    ]
        .filter(Boolean)
        .join(' • ');

    const canonicalUrl =
        product.shop_slug
            ? route('shops.products.show', { slug: product.shop_slug, id: product.id })
            : route('shop.show', product.id);

    const productJsonLd = {
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: product.name,
        description: product.description,
        image: product.image ? [product.image] : undefined,
        brand: product.brand
            ? { '@type': 'Brand', name: product.brand }
            : undefined,
        offers: {
            '@type': 'Offer',
            priceCurrency: 'GHS',
            price: ((product.price_cents ?? 0) / 100).toFixed(2),
            availability: soldOut
                ? 'https://schema.org/OutOfStock'
                : 'https://schema.org/InStock',
            url: canonicalUrl,
        },
    };

    return (
        <>
            <SeoHead
                title={product.name}
                description={product.description}
                image={product.image}
                url={canonicalUrl}
                type="product"
                jsonLd={productJsonLd}
            />

            <div className="flex min-h-screen flex-col bg-[#f7f5f2] text-stone-900 antialiased">
                <header className="border-b border-stone-200/90 bg-white/95 backdrop-blur">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
                        <Link
                            href={
                                store?.slug
                                    ? route('shops.show', store.slug)
                                    : route('shop.index')
                            }
                            className="shrink-0 text-sm font-semibold text-[#5c4d3d] hover:text-market hover:underline"
                        >
                            {store?.slug ? `← Back to ${store.name}` : '← Back to shop'}
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
                            items={
                                store?.slug
                                    ? [
                                          { label: 'Home', href: '/' },
                                          { label: 'Shop', href: route('shop.index') },
                                          { label: store.name, href: route('shops.show', store.slug) },
                                          { label: product.name },
                                      ]
                                    : [
                                          { label: 'Home', href: '/' },
                                          { label: 'Shop', href: route('shop.index') },
                                          { label: category, href: route('shop.index', { q }) },
                                          { label: product.name },
                                      ]
                            }
                        />
                    </div>
                </div>

                <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                    <div className="grid gap-10 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)] lg:gap-14 xl:gap-16">
                        {/* Gallery */}
                        <div>
                            <div className="relative overflow-hidden rounded-2xl border border-stone-200/80 bg-stone-100 shadow-sm ring-1 ring-black/5">
                                <div className="aspect-[4/3] sm:aspect-[16/11]">
                                    <img
                                        src={mainSrc}
                                        alt=""
                                        className={`h-full w-full object-cover ${soldOut ? 'opacity-60 grayscale-[35%]' : ''}`}
                                    />
                                    {soldOut && (
                                        <div className="absolute inset-0 flex items-center justify-center bg-stone-900/20">
                                            <span className="rounded-lg bg-stone-900 px-5 py-2.5 text-sm font-bold uppercase tracking-[0.2em] text-white shadow-lg">
                                                Sold out
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="mt-4 flex gap-3 overflow-x-auto pb-1 sm:gap-4">
                                {gallery.map((src, i) => (
                                    <button
                                        key={src + i}
                                        type="button"
                                        onClick={() => setActiveImage(i)}
                                        className={`relative h-20 w-20 shrink-0 overflow-hidden rounded-xl border-2 transition sm:h-24 sm:w-24 ${
                                            activeImage === i
                                                ? 'border-[#5c4d3d] ring-2 ring-[#5c4d3d]/20'
                                                : 'border-transparent opacity-80 hover:opacity-100'
                                        }`}
                                    >
                                        <img src={src} alt="" className="h-full w-full object-cover" />
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Product info */}
                        <div className="flex flex-col">
                            <div className="flex flex-wrap items-center gap-3">
                                {soldOut && (
                                    <span className="rounded-full bg-stone-900 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">
                                        Sold out
                                    </span>
                                )}
                                {product.badges?.map((b) => (
                                    <span
                                        key={b.text}
                                        className={`rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide ${badgeClasses(
                                            b.variant
                                        )}`}
                                    >
                                        {b.text}
                                    </span>
                                ))}
                                {(product.reviews_count ?? 0) > 0 && product.rating != null && (
                                    <div className="flex flex-wrap items-center gap-2 text-sm text-stone-500">
                                        <StarRow rating={product.rating} />
                                        <span className="text-stone-600">
                                            ({product.reviews_count}{' '}
                                            {product.reviews_count === 1 ? 'review' : 'reviews'})
                                        </span>
                                    </div>
                                )}
                            </div>

                            <h1 className="mt-4 text-3xl font-bold leading-tight tracking-tight text-[#3d3429] sm:text-4xl lg:text-[2.5rem]">
                                {product.name}
                            </h1>

                            {product.brand && product.brand !== product.name && (
                                <p className="mt-2 text-sm font-medium text-stone-600">
                                    Brand: <span className="font-semibold text-[#5c4d3d]">{product.brand}</span>
                                </p>
                            )}

                            <dl className="mt-5 space-y-3 rounded-2xl border border-stone-200/80 bg-white/80 p-4 sm:p-5">
                                <ProductDetailRow label="Brand" value={product.brand} />
                                <ProductDetailRow label="Category" value={product.category} />
                                <ProductDetailRow label="Condition" value={product.condition_label} />
                                <ProductDetailRow label="Size" value={product.size} />
                                <ProductDetailRow label="SKU" value={product.sku} />
                                <ProductDetailRow
                                    label="Materials"
                                    value={product.material_tags?.length ? product.material_tags.join(', ') : null}
                                />
                                <ProductDetailRow
                                    label="Customization"
                                    value={
                                        product.allows_customization
                                            ? 'Available — contact seller after ordering'
                                            : 'Not available'
                                    }
                                />
                            </dl>

                            <div className="mt-4 flex flex-wrap items-baseline gap-3">
                                <ProductPrice
                                    price={product.price}
                                    compareAtPrice={product.compare_at_price}
                                    priceClassName="text-3xl font-bold text-[#5c4d3d] sm:text-4xl"
                                    compareClassName="text-lg text-stone-400 line-through"
                                />
                            </div>

                            <div className="mt-6 rounded-2xl border border-stone-200/80 bg-stone-100/80 p-4 sm:p-5">
                                <div className="flex items-start gap-3">
                                    <div
                                        className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-orange-400 text-lg font-bold text-white shadow-inner"
                                        aria-hidden
                                    >
                                        {product.seller?.initial ?? '?'}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm text-stone-600">
                                            Sold by{' '}
                                            {product.seller?.slug ? (
                                                <Link
                                                    href={route('shops.show', product.seller.slug)}
                                                    className="font-semibold text-stone-900 hover:text-market hover:underline"
                                                >
                                                    {product.seller.name}
                                                </Link>
                                            ) : (
                                                <span className="font-semibold text-stone-900">
                                                    {product.seller?.name}
                                                </span>
                                            )}
                                        </p>
                                        {product.seller?.approved && (
                                            <p className="mt-1 flex items-center gap-1 text-xs font-medium text-emerald-700">
                                                <IconCheckVerified className="h-4 w-4 text-emerald-600" />
                                                Approved seller
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {product.tags?.length > 0 && (
                                <div className="mt-6 flex flex-wrap gap-2">
                                    {product.tags.map((tag) => (
                                        <span
                                            key={tag.label}
                                            className={`inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold ${tagToneClasses(
                                                tag.tone
                                            )}`}
                                        >
                                            {tag.label}
                                        </span>
                                    ))}
                                </div>
                            )}

                            <p className="mt-6 text-base leading-relaxed text-stone-600">{product.description}</p>

                            {product.requires_size && (
                                <ProductSizeGrid
                                    sizeOptions={product.size_options}
                                    selectedKey={product.size_key}
                                    selectedLabel={product.size}
                                />
                            )}

                            {soldOut ? (
                                <p className="mt-6 text-sm font-medium text-stone-600">
                                    This item is currently sold out and cannot be ordered.
                                </p>
                            ) : stockQuantity > 0 ? (
                                <p className="mt-6 text-sm text-stone-600">
                                    {remainingStock > 0
                                        ? `${stockQuantity} in stock${inCartQty > 0 ? ` · ${inCartQty} in your cart` : ''}`
                                        : 'All available stock is already in your cart'}
                                </p>
                            ) : null}

                            <div className="mt-8 flex flex-wrap items-stretch gap-3">
                            <button
                                type="button"
                                onClick={() => {
                                    if (soldOut) {
                                        return;
                                    }
                                    addItem({
                                        productId: product.id,
                                        name: product.name,
                                        image: mainSrc,
                                        priceLabel: product.price,
                                        attributes,
                                        stockQuantity,
                                        vendorUserId: product.seller?.user_id ?? product.vendor_user_id,
                                        vendorName: product.seller?.name ?? product.maker,
                                        vendorSlug: product.seller?.slug ?? product.shop_slug,
                                    });
                                }}
                                disabled={soldOut || remainingStock < 1}
                                className="inline-flex min-h-[3rem] flex-1 items-center justify-center gap-2 rounded-xl bg-[#5c4d3d] px-8 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-[#4a3e32] disabled:cursor-not-allowed disabled:bg-stone-400 disabled:opacity-100 sm:min-w-[240px]"
                            >
                                    {!soldOut && <IconCart className="h-5 w-5 shrink-0" />}
                                    {soldOut ? 'Sold out' : remainingStock < 1 ? 'Maximum in cart' : 'Add to Cart'}
                                </button>
                                <button
                                    type="button"
                                    className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-stone-200 bg-white text-stone-600 shadow-sm transition hover:border-rose-200 hover:text-rose-500"
                                    aria-label="Add to wishlist"
                                >
                                    <IconHeart className="h-5 w-5" />
                                </button>
                            </div>

                            {product.shipping_note && (
                                <p className="mt-4 flex items-center gap-2 text-sm text-stone-500">
                                    <IconTruck className="h-5 w-5 shrink-0 text-stone-400" aria-hidden />
                                    {product.shipping_note}
                                </p>
                            )}

                            <div className="mt-10 space-y-0 divide-y divide-stone-200 border-t border-stone-200">
                                <div>
                                    <button
                                        type="button"
                                        onClick={() => setOpenAccordion(openAccordion === 'material' ? null : 'material')}
                                        className="flex w-full items-center justify-between py-4 text-left text-sm font-semibold text-stone-900 transition hover:text-[#5c4d3d]"
                                    >
                                        Material &amp; Care
                                        <span
                                            className={`text-xl font-light text-stone-400 transition ${openAccordion === 'material' ? 'rotate-45' : ''}`}
                                            aria-hidden
                                        >
                                            +
                                        </span>
                                    </button>
                                    {openAccordion === 'material' && (
                                        <div className="pb-4 text-sm leading-relaxed text-stone-600">
                                            {product.material_care}
                                        </div>
                                    )}
                                </div>
                                <div>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setOpenAccordion(openAccordion === 'shipping' ? null : 'shipping')
                                        }
                                        className="flex w-full items-center justify-between py-4 text-left text-sm font-semibold text-stone-900 transition hover:text-[#5c4d3d]"
                                    >
                                        Shipping &amp; Returns
                                        <span
                                            className={`text-xl font-light text-stone-400 transition ${openAccordion === 'shipping' ? 'rotate-45' : ''}`}
                                            aria-hidden
                                        >
                                            +
                                        </span>
                                    </button>
                                    {openAccordion === 'shipping' && (
                                        <div className="pb-4 text-sm leading-relaxed text-stone-600">
                                            {product.shipping_returns}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Reviews */}
                    {product.reviews?.length > 0 && (
                        <section className="mt-16 border-t border-stone-200/90 pt-12 sm:mt-20 sm:pt-14">
                            <div className="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h2 className="text-2xl font-bold tracking-tight text-[#3d3429] sm:text-3xl">
                                        Love from Parents
                                    </h2>
                                    <p className="mt-2 max-w-xl text-sm text-stone-600 sm:text-base">
                                        Real feedback from families who bought this item—so you know what to expect.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    className="shrink-0 rounded-full bg-market px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-market-hover"
                                    disabled
                                    title="Reviews are not available yet"
                                >
                                    Write a Review
                                </button>
                            </div>

                            <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {product.reviews.map((r, idx) => (
                                    <article
                                        key={`${r.author}-${idx}`}
                                        className="rounded-2xl border border-stone-200/80 bg-white p-5 shadow-sm ring-1 ring-black/[0.03]"
                                    >
                                        <StarRow rating={r.rating} className="mb-3" />
                                        <h3 className="font-bold text-stone-900">{r.title}</h3>
                                        <p className="mt-2 text-sm leading-relaxed text-stone-600">{r.body}</p>
                                        <div className="mt-4 flex items-center gap-3 border-t border-stone-100 pt-4">
                                            <div
                                                className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white ${r.avatar_color || 'bg-stone-400'}`}
                                            >
                                                {r.initial}
                                            </div>
                                            <div>
                                                <p className="text-sm font-semibold text-stone-900">{r.author}</p>
                                                <p className="text-xs text-stone-500">Verified Buyer</p>
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        </section>
                    )}
                </main>

                <SiteFooter />
            </div>
        </>
    );
}
