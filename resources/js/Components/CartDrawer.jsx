import { useEffect, useRef } from 'react';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import { useCart } from '@/context/CartContext';

function IconCartHeader(props) {
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

function IconClose(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" {...props}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    );
}

function IconTrash(props) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" {...props}>
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"
            />
        </svg>
    );
}

function formatGhs(amount) {
    const n = Number(amount) || 0;
    return `GHS ${n.toFixed(2)}`;
}

export default function CartDrawer() {
    const {
        vendor,
        lines,
        isOpen,
        closeCart,
        increment,
        decrement,
        removeLine,
        clearCart,
        syncStockLevels,
        subtotal,
        count,
    } = useCart();
    const syncedOnOpenRef = useRef(false);

    useEffect(() => {
        if (!isOpen) {
            syncedOnOpenRef.current = false;
            return undefined;
        }

        if (syncedOnOpenRef.current || lines.length === 0) {
            return undefined;
        }

        syncedOnOpenRef.current = true;
        const productIds = [...new Set(lines.map((line) => line.productId))];

        axios
            .post(route('shop.cart-stock'), { product_ids: productIds })
            .then((response) => {
                syncStockLevels(response.data?.stocks ?? {});
            })
            .catch(() => {
                /* keep local limits if sync fails */
            });

        return undefined;
    }, [isOpen, lines, syncStockLevels]);

    useEffect(() => {
        if (!isOpen) return undefined;
        const onKey = (e) => {
            if (e.key === 'Escape') closeCart();
        };
        document.addEventListener('keydown', onKey);
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = prev;
        };
    }, [isOpen, closeCart]);

    return (
        <div
            className={`fixed inset-0 z-[200] transition-[visibility] duration-300 ${isOpen ? 'visible' : 'invisible'}`}
            aria-hidden={!isOpen}
        >
            <button
                type="button"
                className={`absolute inset-0 bg-black/50 transition-opacity duration-300 ${isOpen ? 'opacity-100' : 'pointer-events-none opacity-0'}`}
                onClick={closeCart}
                aria-label="Close cart overlay"
            />

            <aside
                className={`absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-2xl transition-transform duration-300 ease-out ${
                    isOpen ? 'translate-x-0' : 'pointer-events-none translate-x-full'
                }`}
                role="dialog"
                aria-modal="true"
                aria-labelledby="cart-drawer-title"
            >
                <header className="flex shrink-0 items-center justify-between border-b border-neutral-200 px-5 py-4">
                    <h2 id="cart-drawer-title" className="flex items-center gap-2 text-sm font-bold tracking-wide text-neutral-900">
                        <IconCartHeader className="h-5 w-5" aria-hidden />
                        YOUR CART ({count})
                    </h2>
                    <button
                        type="button"
                        onClick={closeCart}
                        className="rounded-full p-2 text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900"
                        aria-label="Close cart"
                    >
                        <IconClose className="h-5 w-5" />
                    </button>
                </header>

                {vendor?.name && lines.length > 0 ? (
                    <div className="shrink-0 border-b border-neutral-100 bg-neutral-50 px-5 py-3">
                        <p className="text-xs font-medium uppercase tracking-wide text-neutral-500">Shopping from</p>
                        {vendor.slug ? (
                            <Link
                                href={route('shops.show', vendor.slug)}
                                onClick={closeCart}
                                className="mt-0.5 text-sm font-bold text-neutral-900 hover:text-market hover:underline"
                            >
                                {vendor.name}
                            </Link>
                        ) : (
                            <p className="mt-0.5 text-sm font-bold text-neutral-900">{vendor.name}</p>
                        )}
                    </div>
                ) : null}

                <div className="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                    {lines.length === 0 ? (
                        <p className="text-center text-sm text-neutral-500">Your cart is empty.</p>
                    ) : (
                        <ul className="space-y-6">
                            {lines.map((line) => (
                                <li key={line.lineId} className="flex gap-3 border-b border-neutral-100 pb-6 last:border-0">
                                    <div className="h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-neutral-200 bg-neutral-50">
                                        <img src={line.image} alt="" className="h-full w-full object-cover" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-bold uppercase tracking-wide text-neutral-900">{line.name}</p>
                                        {line.attributes ? (
                                            <p className="mt-1 text-xs uppercase tracking-wide text-neutral-500">{line.attributes}</p>
                                        ) : null}
                                        <p className="mt-2 text-sm font-bold text-neutral-900">{line.priceLabel}</p>
                                        <div className="mt-3 flex flex-col gap-1">
                                            <div className="inline-flex w-fit items-center border border-neutral-300">
                                                <button
                                                    type="button"
                                                    className="px-2.5 py-1 text-sm font-medium text-neutral-700 transition hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-40"
                                                    onClick={() => decrement(line.lineId)}
                                                    disabled={line.qty <= 1}
                                                    aria-label="Decrease quantity"
                                                >
                                                    −
                                                </button>
                                                <span className="min-w-[2rem] border-x border-neutral-300 px-2 py-1 text-center text-sm font-semibold">
                                                    {line.qty}
                                                </span>
                                                <button
                                                    type="button"
                                                    className="px-2.5 py-1 text-sm font-medium text-neutral-700 transition hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-40"
                                                    onClick={() => increment(line.lineId)}
                                                    disabled={line.qty >= (line.maxStock ?? 99)}
                                                    aria-label="Increase quantity"
                                                >
                                                    +
                                                </button>
                                            </div>
                                            {line.maxStock && line.qty >= line.maxStock ? (
                                                <p className="text-xs text-amber-700">
                                                    Only {line.maxStock} in stock
                                                </p>
                                            ) : null}
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => removeLine(line.lineId)}
                                        className="shrink-0 self-start rounded p-1.5 text-neutral-400 transition hover:bg-red-50 hover:text-red-600"
                                        aria-label={`Remove ${line.name}`}
                                    >
                                        <IconTrash className="h-5 w-5" />
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <footer className="shrink-0 border-t border-neutral-200 bg-white px-5 py-5">
                    <div className="flex items-center justify-between text-sm font-bold text-neutral-900">
                        <span className="uppercase tracking-wide">Subtotal</span>
                        <span>{formatGhs(subtotal)}</span>
                    </div>
                    <p className="mt-2 text-[10px] font-medium uppercase tracking-wider text-neutral-500">
                        Shipping and taxes calculated at checkout
                    </p>
                    <Link
                        href={route('checkout.index')}
                        onClick={closeCart}
                        className={`mt-5 block w-full bg-neutral-900 py-3.5 text-center text-xs font-bold uppercase tracking-widest text-white transition hover:bg-neutral-800 ${
                            lines.length === 0 ? 'pointer-events-none opacity-40' : ''
                        }`}
                        aria-disabled={lines.length === 0}
                    >
                        Proceed to checkout
                    </Link>
                    <button
                        type="button"
                        disabled={lines.length === 0}
                        onClick={() => {
                            clearCart();
                        }}
                        className="mt-3 w-full border border-neutral-900 bg-white py-3.5 text-xs font-bold uppercase tracking-widest text-neutral-900 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Clear cart
                    </button>
                </footer>
            </aside>
        </div>
    );
}
